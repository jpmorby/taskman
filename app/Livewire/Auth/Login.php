<?php

namespace App\Livewire\Auth;

use App\Models\SocialIdentity;
use App\Models\User;
use Exception;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticationOptionsAction;

#[Layout('components.layouts.auth')]
class Login extends Component
{
    /**
     * The OAuth providers that are actually wired up: each one has credentials in
     * config/services.php, an installed Socialite driver and a button in the UI.
     * Apple, LinkedIn and Azure are commented-out stubs in config/services.php and
     * WorkOS is not a Socialite driver, so none of them belong here.
     *
     * The {provider} route parameter is constrained to this list (see routes/web.php)
     * so an unknown slug 404s instead of blowing up inside Socialite's manager.
     *
     * @var list<string>
     */
    public const PROVIDERS = ['github', 'google', 'discord'];

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public string $redirectTo = '/dashboard';

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    public function authenticateWithPasskey(): void
    {
        $optionsJson = app(GeneratePasskeyAuthenticationOptionsAction::class)->execute();
        session()->put('passkey_auth_options', $optionsJson);
        $this->dispatch('passkey-authenticate', options: $optionsJson);
    }

    public function confirmPasskeyAuth(array $credential): void
    {
        $optionsJson = session()->pull('passkey_auth_options');

        if (! $optionsJson) {
            $this->addError('passkey', __('Authentication session expired. Please try again.'));

            return;
        }

        $passkey = app(FindPasskeyToAuthenticateAction::class)->execute(
            json_encode($credential),
            $optionsJson,
        );

        if (! $passkey) {
            $this->addError('passkey', __('The passkey could not be verified. Please try again.'));

            return;
        }

        Auth::login($passkey->authenticatable);
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function redirectToProvider(string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        try {
            return Socialite::driver($provider)->redirect();
        } catch (Exception $e) {
            // Almost always a misconfigured client id/secret/redirect. Log it, since
            // the user can do nothing about it and we otherwise lose the cause.
            Log::error('Could not start social login.', [
                'provider' => $provider,
                'exception' => $e,
            ]);

            return $this->failedSocialLogin(__('We could not start signing you in with :provider. Please try again.', [
                'provider' => Str::title($provider),
            ]));
        }
    }

    public function handleProviderCallback(string $provider)
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        try {
            $providerUser = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            // InvalidStateException (stale/forged callback), a rejected token exchange
            // or a bad secret all land here. Record which, then say something useful.
            Log::warning('Social login callback failed.', [
                'provider' => $provider,
                'exception' => $e,
            ]);

            return $this->failedSocialLogin(__('Signing in with :provider did not complete. Please try again.', [
                'provider' => Str::title($provider),
            ]));
        }

        $authUser = $this->findOrCreateUser($providerUser, $provider);

        if (! $authUser) {
            return $this->failedSocialLogin(__('An account already exists for that email address. Please sign in with your password. Once that address is verified you will be able to sign in with :provider.', [
                'provider' => Str::title($provider),
            ]));
        }

        Auth::login($authUser, true);
        Session::regenerate();

        return redirect($this->redirectTo);
    }

    /**
     * Resolve the local account for a provider identity, creating one if needed.
     *
     * Returns null when the provider's email already belongs to a local account
     * that we are not willing to link automatically; the caller turns that into a
     * message rather than silently signing the visitor in as somebody else.
     */
    public function findOrCreateUser($providerUser, string $provider): ?User
    {
        $identity = SocialIdentity::whereProviderName($provider)
            ->whereProviderId($providerUser->getId())
            ->first();

        // An identity whose user row is gone is an orphan: rows created before the
        // cascading foreign key was added survived account deletion. Returning
        // $identity->user here would hand Auth::login() a null and throw.
        if ($identity && $identity->user) {
            return $identity->user;
        }

        $email = $providerUser->getEmail();
        $providerVerified = $this->providerVerifiedEmail($providerUser, $provider);

        // Providers may return no email at all, and users.email is nullable, so
        // `where email = null` would match the first email-less OAuth account ever
        // created. An absent email can never identify an existing account.
        $existing = filled($email) ? User::whereEmail($email)->first() : null;

        if ($existing) {
            // Only adopt an existing account when both sides of the match are
            // trustworthy: the provider says it verified the address, and the local
            // account has verified it too. Either half alone is forgeable - a
            // provider account with an arbitrary email claim, or a local account
            // registered at someone else's address before they ever signed in.
            if (! $providerVerified || ! $existing->hasVerifiedEmail()) {
                return null;
            }

            $user = $existing;
        } else {
            $user = new User;
            $user->fill([
                'email' => filled($email) ? $email : null,
                'name' => $this->resolveName($providerUser, $provider),
            ]);

            // The provider has already proved the address, and this path fires no
            // Registered event, so nothing would ever send a verification mail.
            // Leaving it null would strand the account behind the unverified banner
            // and block it from linking a second provider later. Not fillable, so
            // it is set directly rather than mass-assigned.
            $user->email_verified_at = $providerVerified && filled($email) ? now() : null;

            $user->save();
        }

        // Clear the orphan first: (provider_name, provider_id) is unique.
        $identity?->delete();

        $user->identities()->create([
            'provider_id' => $providerUser->getId(),
            'provider_name' => $provider,
        ]);

        return $user;
    }

    /**
     * Did the provider itself assert that it verified this email address?
     *
     * The flag lives in the raw payload, and each provider spells it differently.
     * Anything we do not recognise is treated as unverified.
     */
    protected function providerVerifiedEmail($providerUser, string $provider): bool
    {
        $raw = (array) ($providerUser->user ?? []);

        return match ($provider) {
            // OIDC userinfo; Socialite also copies it to `verified_email`.
            'google' => (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? false),
            // Discord exposes `email` whatever its state, and `verified` alongside it.
            'discord' => (bool) ($raw['verified'] ?? false),
            // Socialite's GitHub driver requests the `user:email` scope and returns
            // only the address GitHub reports as primary *and* verified
            // (GithubProvider::getEmailByToken()), so a present email is a verified
            // one. If that scope is ever dropped the email becomes the unverified
            // public profile field, and this must change with it.
            'github' => filled($providerUser->getEmail()),
            default => false,
        };
    }

    /**
     * users.name is NOT NULL, and GitHub accounts with no display name return null,
     * so fall back through the other identifiers the provider gave us.
     */
    protected function resolveName($providerUser, string $provider): string
    {
        return $providerUser->getName()
            ?: $providerUser->getNickname()
            ?: Str::before((string) $providerUser->getEmail(), '@')
            ?: Str::title($provider).' user';
    }

    /**
     * Send the visitor back to the login screen with something they can act on.
     */
    protected function failedSocialLogin(string $message)
    {
        return redirect()->route('login')->with('status', $message);
    }
}
