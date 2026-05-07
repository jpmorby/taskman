# Passkey Authentication Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add passkey (WebAuthn discoverable credential) login to Taskman using `spatie/laravel-passkeys`, with management in Settings and a "Sign in with a passkey" button on the Login page.

**Architecture:** `spatie/laravel-passkeys` owns all WebAuthn cryptography. Livewire components own the server-side flow (generating challenges, storing credentials, verifying assertions). A global Alpine component (`passkeys.js`) owns the browser WebAuthn API calls and base64url encoding. Passwords remain as a fallback; no changes to the `users` table.

**Tech Stack:** Laravel 13, Livewire 4, FluxUI (Flux), Alpine.js (bundled with Livewire), Pest 4, `spatie/laravel-passkeys`

---

## File Map

**Create:**
- `app/Livewire/Settings/Passkeys.php` — settings Livewire component (list/add/delete passkeys)
- `resources/views/livewire/settings/passkeys.blade.php` — settings view
- `resources/js/passkeys.js` — Alpine components for WebAuthn browser calls
- `tests/Feature/Auth/PasskeyTest.php` — all passkey tests

**Modify:**
- `composer.json` — add `spatie/laravel-passkeys` (via `composer require`)
- `app/Models/User.php` — add `HasPasskeys` trait
- `routes/web.php` — add `settings/passkeys` route
- `resources/views/components/settings/layout.blade.php` — add "Passkeys" nav item
- `resources/js/app.js` — import `passkeys.js`
- `app/Livewire/Auth/Login.php` — add `authenticateWithPasskey()` and `confirmPasskeyAuth()`
- `resources/views/livewire/auth/login.blade.php` — add passkey sign-in button

---

## Task 1: Install and Configure Package

**Files:**
- Run: `composer require spatie/laravel-passkeys`
- Publish: migration and config
- Run: `php artisan migrate`

- [ ] **Step 1: Install the package**

```bash
composer require spatie/laravel-passkeys
```

Expected: Package installs, `composer.json` and `composer.lock` updated.

- [ ] **Step 2: Publish migration and config**

```bash
php artisan vendor:publish --tag="passkeys-migrations"
php artisan vendor:publish --tag="passkeys-config"
```

Expected: A new migration in `database/migrations/` creating the `passkeys` table, and `config/passkeys.php` created.

- [ ] **Step 3: Review config/passkeys.php**

Open `config/passkeys.php` and confirm the `relying_party_name` and `relying_party_id` match your app. Set:
```php
'relying_party_name' => env('APP_NAME', 'Taskman'),
'relying_party_id' => env('APP_URL_HOST', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
```

Add `APP_URL_HOST` to `.env.example` (optional — `parse_url` handles it at runtime from `APP_URL`).

- [ ] **Step 4: Run the migration**

```bash
php artisan migrate
```

Expected: `passkeys` table created with columns: `id`, `user_id`, `name`, `credential_id`, `public_key`, `sign_count`, `transports`, `created_at`, `updated_at`.

- [ ] **Step 5: Verify action class names from the package**

```bash
find vendor/spatie/laravel-passkeys/src -name "*.php" | xargs grep "class.*Action" | head -20
```

Confirm the action classes are named (adjust the plan if different):
- `Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction`
- `Spatie\LaravelPasskeys\Actions\StorePasskeyAction`
- `Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticateOptionsAction`
- `Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction`

Also check method signatures:
```bash
grep -A5 "public function execute" vendor/spatie/laravel-passkeys/src/Actions/*.php
```

- [ ] **Step 6: Commit**

```bash
git add composer.json composer.lock config/passkeys.php database/migrations/*passkeys*
git commit -m "feat: install and configure spatie/laravel-passkeys"
```

---

## Task 2: Add HasPasskeys Trait to User Model

**Files:**
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Auth/PasskeyTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Auth/PasskeyTest.php`:

```php
<?php

use App\Models\User;
use Spatie\LaravelPasskeys\Models\Passkey;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('user has a passkeys relationship', function () {
    $user = User::factory()->create();

    expect($user->passkeys())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

it('user can have multiple passkeys', function () {
    $user = User::factory()->create();

    $user->passkeys()->create([
        'name' => 'MacBook',
        'credential_id' => 'credential-id-1',
        'public_key' => 'public-key-1',
        'sign_count' => 0,
        'transports' => [],
    ]);

    $user->passkeys()->create([
        'name' => 'iPhone',
        'credential_id' => 'credential-id-2',
        'public_key' => 'public-key-2',
        'sign_count' => 0,
        'transports' => [],
    ]);

    expect($user->passkeys()->count())->toBe(2);
});
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
./vendor/bin/pest tests/Feature/Auth/PasskeyTest.php
```

Expected: FAIL — `passkeys()` method not found on User.

- [ ] **Step 3: Add the HasPasskeys trait to User model**

In `app/Models/User.php`, add the import and trait:

```php
use Spatie\LaravelPasskeys\Models\Concerns\HasPasskeys;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasPasskeys, Notifiable;
```

- [ ] **Step 4: Run the tests to verify they pass**

```bash
./vendor/bin/pest tests/Feature/Auth/PasskeyTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php tests/Feature/Auth/PasskeyTest.php
git commit -m "feat: add HasPasskeys trait to User model"
```

---

## Task 3: Add Route and Settings Navigation Item

**Files:**
- Modify: `routes/web.php`
- Modify: `resources/views/components/settings/layout.blade.php`
- Test: `tests/Feature/Auth/PasskeyTest.php` (append)

- [ ] **Step 1: Write the failing route test**

Append to `tests/Feature/Auth/PasskeyTest.php`:

```php
it('settings/passkeys route requires authentication', function () {
    $response = $this->get(route('settings.passkeys'));

    $response->assertRedirect(route('login'));
});

it('authenticated user can access settings/passkeys', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.passkeys'));

    $response->assertOk();
});
```

- [ ] **Step 2: Run to verify failures**

```bash
./vendor/bin/pest tests/Feature/Auth/PasskeyTest.php --filter="settings/passkeys"
```

Expected: FAIL — route `settings.passkeys` not found.

- [ ] **Step 3: Add the route to routes/web.php**

In `routes/web.php`, inside the `middleware(['auth'])` group that contains the other settings routes, add:

```php
use App\Livewire\Settings\Passkeys;

// inside the Route::middleware(['auth'])->group(function () { block:
Route::get('settings/passkeys', Passkeys::class)->name('settings.passkeys');
```

The full updated settings group:
```php
Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
    Route::get('settings/passkeys', Passkeys::class)->name('settings.passkeys');
});
```

- [ ] **Step 4: Create a stub Passkeys component so the route resolves**

Create `app/Livewire/Settings/Passkeys.php` with just enough to render:

```php
<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class Passkeys extends Component
{
    public function render()
    {
        return view('livewire.settings.passkeys');
    }
}
```

Create `resources/views/livewire/settings/passkeys.blade.php`:

```blade
<section class="w-full">
    @include('partials.settings-heading')
    <x-settings.layout :heading="__('Passkeys')" :subheading="__('Manage your passkeys for passwordless sign-in')">
        <p>Passkeys coming soon.</p>
    </x-settings.layout>
</section>
```

- [ ] **Step 5: Run the route tests to verify they pass**

```bash
./vendor/bin/pest tests/Feature/Auth/PasskeyTest.php --filter="settings/passkeys"
```

Expected: PASS (2 tests).

- [ ] **Step 6: Add "Passkeys" to the settings navigation**

In `resources/views/components/settings/layout.blade.php`, add after the Appearance nav item:

```blade
<flux:navlist.item :href="route('settings.passkeys')" wire:navigate>{{ __('Passkeys') }}</flux:navlist.item>
```

Full updated file:
```blade
<div class="flex items-start max-md:flex-col">
    <div class="mr-10 w-full pb-4 md:w-[220px]">
        <flux:navlist>
            <flux:navlist.item :href="route('settings.profile')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.password')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.appearance')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
            <flux:navlist.item :href="route('settings.passkeys')" wire:navigate>{{ __('Passkeys') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch max-md:pt-6">
        <flux:heading>{{ $heading ?? '' }}</flux:heading>
        <flux:subheading>{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
```

- [ ] **Step 7: Commit**

```bash
git add routes/web.php app/Livewire/Settings/Passkeys.php resources/views/livewire/settings/passkeys.blade.php resources/views/components/settings/layout.blade.php
git commit -m "feat: add passkeys settings route and nav item"
```

---

## Task 4: Implement Settings\Passkeys Component

**Files:**
- Modify: `app/Livewire/Settings/Passkeys.php`
- Modify: `resources/views/livewire/settings/passkeys.blade.php`
- Test: `tests/Feature/Auth/PasskeyTest.php` (append)

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/Auth/PasskeyTest.php`:

```php
use App\Livewire\Settings\Passkeys as PasskeysPage;
use Livewire\Livewire;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;

it('displays existing passkeys on the settings page', function () {
    $user = User::factory()->create();
    $user->passkeys()->create([
        'name' => 'Work MacBook',
        'credential_id' => 'cred-1',
        'public_key' => 'pk-1',
        'sign_count' => 0,
        'transports' => [],
    ]);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->assertSee('Work MacBook');
});

it('dispatches passkey-register event with options when startRegistration is called', function () {
    $user = User::factory()->create();
    $mockOptions = ['challenge' => 'dGVzdA', 'rp' => ['name' => 'Taskman']];

    $mock = Mockery::mock(GeneratePasskeyRegisterOptionsAction::class);
    $mock->shouldReceive('execute')->once()->with(Mockery::type(User::class))->andReturn($mockOptions);
    app()->instance(GeneratePasskeyRegisterOptionsAction::class, $mock);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->set('newPasskeyName', 'Work MacBook')
        ->call('startRegistration')
        ->assertDispatched('passkey-register');
});

it('stores a passkey and refreshes the list', function () {
    $user = User::factory()->create();
    $credential = ['id' => 'cred-id', 'type' => 'public-key', 'rawId' => 'cred-id'];

    $mock = Mockery::mock(StorePasskeyAction::class);
    $mock->shouldReceive('execute')
        ->once()
        ->with(Mockery::type(User::class), $credential, 'Work MacBook');
    app()->instance(StorePasskeyAction::class, $mock);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->set('newPasskeyName', 'Work MacBook')
        ->call('confirmPasskey', $credential);

    // No assertion needed beyond mock verification (mock expectation fails test if not called)
});

it('deletes a passkey when user has a password set', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);
    $passkey = $user->passkeys()->create([
        'name' => 'Old Phone',
        'credential_id' => 'cred-delete',
        'public_key' => 'pk-delete',
        'sign_count' => 0,
        'transports' => [],
    ]);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->call('removePasskey', $passkey->id);

    expect(Passkey::find($passkey->id))->toBeNull();
});

it('blocks deletion of last passkey when user has no password', function () {
    $user = User::factory()->create(['password' => null]);
    $passkey = $user->passkeys()->create([
        'name' => 'Only Key',
        'credential_id' => 'cred-only',
        'public_key' => 'pk-only',
        'sign_count' => 0,
        'transports' => [],
    ]);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->call('removePasskey', $passkey->id)
        ->assertHasErrors(['passkeys']);

    expect(Passkey::find($passkey->id))->not->toBeNull();
});

it('allows deletion of one of multiple passkeys even with no password', function () {
    $user = User::factory()->create(['password' => null]);
    $passkey1 = $user->passkeys()->create([
        'name' => 'Key One',
        'credential_id' => 'cred-one',
        'public_key' => 'pk-one',
        'sign_count' => 0,
        'transports' => [],
    ]);
    $user->passkeys()->create([
        'name' => 'Key Two',
        'credential_id' => 'cred-two',
        'public_key' => 'pk-two',
        'sign_count' => 0,
        'transports' => [],
    ]);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->call('removePasskey', $passkey1->id);

    expect(Passkey::find($passkey1->id))->toBeNull();
});
```

- [ ] **Step 2: Run to verify failures**

```bash
./vendor/bin/pest tests/Feature/Auth/PasskeyTest.php --filter="passkey"
```

Expected: Multiple failures — `startRegistration()`, `confirmPasskey()`, `removePasskey()` methods not found.

- [ ] **Step 3: Implement the Passkeys component**

Replace `app/Livewire/Settings/Passkeys.php` with:

```php
<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Collection;
use Livewire\Component;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;

class Passkeys extends Component
{
    public Collection $passkeys;

    public string $newPasskeyName = '';

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        $this->loadPasskeys();
    }

    public function startRegistration(): void
    {
        $options = app(GeneratePasskeyRegisterOptionsAction::class)->execute(auth()->user());
        $this->dispatch('passkey-register', options: json_encode($options));
    }

    public function confirmPasskey(array $credential): void
    {
        app(StorePasskeyAction::class)->execute(
            user: auth()->user(),
            passkeyAttributes: $credential,
            keyName: $this->newPasskeyName,
        );

        $this->newPasskeyName = '';
        $this->loadPasskeys();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function removePasskey(int $id): void
    {
        $passkey = auth()->user()->passkeys()->findOrFail($id);

        if (auth()->user()->passkeys()->count() === 1 && ! auth()->user()->hasPassword()) {
            $this->addError('passkeys', __('You cannot remove your only passkey without a password set.'));

            return;
        }

        $passkey->delete();
        $this->confirmingDeleteId = null;
        $this->loadPasskeys();
    }

    public function render()
    {
        return view('livewire.settings.passkeys');
    }

    private function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()->orderByDesc('created_at')->get();
    }
}
```

> **Note:** `StorePasskeyAction::execute()` parameter names (`user`, `passkeyAttributes`, `keyName`) were inferred from the package. If they differ, check with: `grep -A10 "public function execute" vendor/spatie/laravel-passkeys/src/Actions/StorePasskeyAction.php`

- [ ] **Step 4: Run the tests to verify they pass**

```bash
./vendor/bin/pest tests/Feature/Auth/PasskeyTest.php
```

Expected: All passkey tests pass.

- [ ] **Step 5: Build the passkeys settings view**

Replace `resources/views/livewire/settings/passkeys.blade.php` with:

```blade
<section
    class="w-full"
    x-data="passkeyRegister"
    @passkey-register.window="register($event.detail.options)"
>
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Passkeys')"
        :subheading="__('Manage your passkeys for passwordless sign-in')"
    >
        @if ($errors->has('passkeys'))
            <flux:callout variant="danger" class="mb-4">
                {{ $errors->first('passkeys') }}
            </flux:callout>
        @endif

        <template x-if="error">
            <flux:callout variant="danger" class="mb-4" x-text="error"></flux:callout>
        </template>

        {{-- Existing passkeys list --}}
        @if ($passkeys->isNotEmpty())
            <div class="mb-6 divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                @foreach ($passkeys as $passkey)
                    <div class="flex items-center justify-between px-4 py-3">
                        <div>
                            <flux:text class="font-medium">{{ $passkey->name }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ __('Added') }} {{ $passkey->created_at->diffForHumans() }}
                            </flux:text>
                        </div>

                        <flux:modal.trigger name="confirm-delete-{{ $passkey->id }}">
                            <flux:button
                                variant="danger"
                                size="sm"
                                x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-{{ $passkey->id }}')"
                                wire:click="confirmDelete({{ $passkey->id }})"
                            >
                                {{ __('Remove') }}
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="confirm-delete-{{ $passkey->id }}" class="max-w-md" focusable>
                            <div class="flex flex-col gap-4">
                                <flux:heading size="lg">{{ __('Remove passkey?') }}</flux:heading>
                                <flux:subheading>
                                    {{ __('This will remove the passkey ":name". You will no longer be able to sign in with it.', ['name' => $passkey->name]) }}
                                </flux:subheading>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <flux:button
                                        variant="danger"
                                        wire:click="removePasskey({{ $passkey->id }})"
                                    >
                                        {{ __('Remove') }}
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                @endforeach
            </div>
        @else
            <flux:text class="mb-6 text-zinc-500">
                {{ __('No passkeys registered yet.') }}
            </flux:text>
        @endif

        {{-- Add passkey --}}
        <flux:modal.trigger name="add-passkey">
            <flux:button
                variant="primary"
                x-on:click.prevent="$dispatch('open-modal', 'add-passkey')"
            >
                {{ __('Add passkey') }}
            </flux:button>
        </flux:modal.trigger>

        <flux:modal name="add-passkey" class="max-w-md" focusable>
            <div class="flex flex-col gap-4">
                <flux:heading size="lg">{{ __('Add a passkey') }}</flux:heading>
                <flux:subheading>
                    {{ __('Give this passkey a name so you can identify it later.') }}
                </flux:subheading>
                <flux:input
                    wire:model="newPasskeyName"
                    :label="__('Passkey name')"
                    placeholder="{{ __('e.g. Work MacBook') }}"
                    autofocus
                />
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="startRegistration">
                        {{ __('Add passkey') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </x-settings.layout>
</section>
```

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Settings/Passkeys.php resources/views/livewire/settings/passkeys.blade.php tests/Feature/Auth/PasskeyTest.php
git commit -m "feat: implement Settings\Passkeys Livewire component and view"
```

---

## Task 5: Add Passkey Authentication to Login Component

**Files:**
- Modify: `app/Livewire/Auth/Login.php`
- Modify: `resources/views/livewire/auth/login.blade.php`
- Test: `tests/Feature/Auth/PasskeyTest.php` (append)

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/Auth/PasskeyTest.php`:

```php
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Auth;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticateOptionsAction;
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Models\Passkey;

it('dispatches passkey-authenticate event when passkey sign-in is triggered', function () {
    $mockOptions = ['challenge' => 'dGVzdA', 'allowCredentials' => []];

    $mock = Mockery::mock(GeneratePasskeyAuthenticateOptionsAction::class);
    $mock->shouldReceive('execute')->once()->andReturn($mockOptions);
    app()->instance(GeneratePasskeyAuthenticateOptionsAction::class, $mock);

    Livewire::test(Login::class)
        ->call('authenticateWithPasskey')
        ->assertDispatched('passkey-authenticate');
});

it('logs in user and redirects with valid passkey assertion', function () {
    $user = User::factory()->create();
    $credential = ['id' => 'cred-id', 'type' => 'public-key'];

    $passkey = Mockery::mock(Passkey::class)->makePartial();
    $passkey->user = $user;

    $mock = Mockery::mock(FindPasskeyToAuthenticateAction::class);
    $mock->shouldReceive('execute')->once()->with($credential)->andReturn($passkey);
    app()->instance(FindPasskeyToAuthenticateAction::class, $mock);

    Livewire::test(Login::class)
        ->call('confirmPasskeyAuth', $credential)
        ->assertRedirect(route('dashboard'));

    expect(Auth::user()->id)->toBe($user->id);
});

it('shows an error when passkey assertion is invalid', function () {
    $credential = ['id' => 'bad-cred', 'type' => 'public-key'];

    $mock = Mockery::mock(FindPasskeyToAuthenticateAction::class);
    $mock->shouldReceive('execute')->once()->with($credential)->andThrow(new \Exception('Invalid assertion'));
    app()->instance(FindPasskeyToAuthenticateAction::class, $mock);

    Livewire::test(Login::class)
        ->call('confirmPasskeyAuth', $credential)
        ->assertHasErrors(['passkey']);
});
```

- [ ] **Step 2: Run to verify failures**

```bash
./vendor/bin/pest tests/Feature/Auth/PasskeyTest.php --filter="passkey sign-in|logs in user|shows an error when passkey"
```

Expected: FAIL — `authenticateWithPasskey()` and `confirmPasskeyAuth()` methods not found.

- [ ] **Step 3: Add passkey methods to Login component**

In `app/Livewire/Auth/Login.php`, add these imports and methods.

Add imports (maintain alphabetical order per Pint):
```php
use Spatie\LaravelPasskeys\Actions\FindPasskeyToAuthenticateAction;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyAuthenticateOptionsAction;
```

Add methods to the class:
```php
public function authenticateWithPasskey(): void
{
    $options = app(GeneratePasskeyAuthenticateOptionsAction::class)->execute();
    $this->dispatch('passkey-authenticate', options: json_encode($options));
}

public function confirmPasskeyAuth(array $credential): void
{
    try {
        $passkey = app(FindPasskeyToAuthenticateAction::class)->execute($credential);
    } catch (\Exception $e) {
        $this->addError('passkey', __('The passkey could not be verified. Please try again.'));

        return;
    }

    Auth::login($passkey->user);
    Session::regenerate();
    $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
}
```

> **Note:** If `FindPasskeyToAuthenticateAction` throws a specific typed exception (e.g. `PasskeyVerificationException`), update the `catch` clause. Check with: `grep -r "Exception\|throw" vendor/spatie/laravel-passkeys/src/Actions/FindPasskeyToAuthenticateAction.php`

- [ ] **Step 4: Run to verify the new tests pass**

```bash
./vendor/bin/pest tests/Feature/Auth/PasskeyTest.php
```

Expected: All tests pass.

- [ ] **Step 5: Add the passkey sign-in button to the login view**

In `resources/views/livewire/auth/login.blade.php`, add after the closing `</form>` tag and before the SSO separator. The full updated file:

```blade
<div
    class="flex flex-col gap-6"
    x-data="passkeyAuthenticate"
    @passkey-authenticate.window="authenticate($event.detail.options)"
>
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input wire:model="email" :label="__('Email address')" type="email" required autofocus autocomplete="email"
            placeholder="email@example.com" />

        <!-- Password -->
        <div class="relative">
            <flux:input wire:model="password" :label="__('Password')" type="password" required
                autocomplete="current-password" :placeholder="__('Password')" />

            @if (Route::has('password.request'))
                <flux:link class="absolute right-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
        </div>
    </form>

    @error('passkey')
        <flux:callout variant="danger">{{ $message }}</flux:callout>
    @enderror

    <template x-if="error">
        <flux:callout variant="danger" x-text="error"></flux:callout>
    </template>

    <div class="flex flex-col gap-3">
        <flux:separator text="{{ __('or') }}" />

        <flux:button
            variant="outline"
            class="w-full"
            wire:click="authenticateWithPasskey"
        >
            <flux:icon name="finger-print" class="mr-2 size-5" />
            {{ __('Sign in with a passkey') }}
        </flux:button>

        <flux:separator text="{{ __('or use Single Sign On with') }}" />

        @include('livewire.auth.sso-buttons')
    </div>

    <flux:separator />

    @if (Route::has('register'))
        <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Don\'t have an account?') }}
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif
</div>
```

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Auth/Login.php resources/views/livewire/auth/login.blade.php tests/Feature/Auth/PasskeyTest.php
git commit -m "feat: add passkey sign-in to Login component and view"
```

---

## Task 6: Create the Alpine JavaScript Component

**Files:**
- Create: `resources/js/passkeys.js`
- Modify: `resources/js/app.js`

- [ ] **Step 1: Create resources/js/passkeys.js**

```javascript
function base64urlToBuffer(base64url) {
    const padded = base64url.replace(/-/g, '+').replace(/_/g, '/')
        + '==='.slice((base64url.length % 4) || 4);
    return Uint8Array.from(atob(padded), c => c.charCodeAt(0)).buffer;
}

function bufferToBase64url(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
}

function parseRegistrationOptions(optionsJson) {
    const options = JSON.parse(optionsJson);
    options.challenge = base64urlToBuffer(options.challenge);
    options.user.id = base64urlToBuffer(options.user.id);
    if (options.excludeCredentials) {
        options.excludeCredentials = options.excludeCredentials.map(c => ({
            ...c,
            id: base64urlToBuffer(c.id),
        }));
    }
    return options;
}

function encodeRegistrationCredential(credential) {
    return {
        id: credential.id,
        rawId: bufferToBase64url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
            attestationObject: bufferToBase64url(credential.response.attestationObject),
        },
        transports: credential.response.getTransports ? credential.response.getTransports() : [],
    };
}

function parseAuthenticationOptions(optionsJson) {
    const options = JSON.parse(optionsJson);
    options.challenge = base64urlToBuffer(options.challenge);
    options.allowCredentials = [];
    return options;
}

function encodeAuthenticationCredential(credential) {
    return {
        id: credential.id,
        rawId: bufferToBase64url(credential.rawId),
        type: credential.type,
        response: {
            clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
            authenticatorData: bufferToBase64url(credential.response.authenticatorData),
            signature: bufferToBase64url(credential.response.signature),
            userHandle: credential.response.userHandle
                ? bufferToBase64url(credential.response.userHandle)
                : null,
        },
    };
}

document.addEventListener('alpine:init', () => {
    Alpine.data('passkeyRegister', () => ({
        error: null,

        async register(optionsJson) {
            this.error = null;
            try {
                const options = parseRegistrationOptions(optionsJson);
                const credential = await navigator.credentials.create({ publicKey: options });
                await this.$wire.confirmPasskey(encodeRegistrationCredential(credential));
            } catch (e) {
                if (e.name === 'InvalidStateError') {
                    this.error = 'This passkey is already registered on this device.';
                } else if (e.name === 'NotAllowedError') {
                    this.error = 'Passkey registration was cancelled.';
                } else {
                    this.error = 'An error occurred during registration. Please try again.';
                }
            }
        },
    }));

    Alpine.data('passkeyAuthenticate', () => ({
        error: null,

        async authenticate(optionsJson) {
            this.error = null;
            try {
                const options = parseAuthenticationOptions(optionsJson);
                const credential = await navigator.credentials.get({ publicKey: options });
                await this.$wire.confirmPasskeyAuth(encodeAuthenticationCredential(credential));
            } catch (e) {
                if (e.name === 'NotAllowedError') {
                    this.error = 'Sign-in was cancelled.';
                } else {
                    this.error = 'An error occurred. Please try again.';
                }
            }
        },
    }));
});
```

- [ ] **Step 2: Update resources/js/app.js to import passkeys.js**

```javascript
import './passkeys.js';
```

- [ ] **Step 3: Build assets**

```bash
npm run build
```

Expected: Build succeeds. Vite bundles `passkeys.js` into `app.js`.

- [ ] **Step 4: Commit**

```bash
git add resources/js/passkeys.js resources/js/app.js public/build
git commit -m "feat: add Alpine passkey registration and authentication components"
```

---

## Task 7: Full Suite, Pint, and Deploy

- [ ] **Step 1: Run the full test suite**

```bash
./vendor/bin/pest --parallel
```

Expected: All tests pass. If any fail, fix them before proceeding.

- [ ] **Step 2: Run Pint**

```bash
./vendor/bin/pint
```

Expected: Any style fixes applied. Re-stage and commit if Pint changed files:

```bash
git add -p
git commit -m "fix: apply Pint style fixes"
```

- [ ] **Step 3: Push to remote**

```bash
git push origin main
```

- [ ] **Step 4: Deploy via Envoy**

```bash
envoy run deploy
```

Expected: Full deployment story runs: backup_db → clone_repo → setup_env → build → publish → optimize → backup_old_version. Verify passkey settings tab appears in production.

---

## Checklist: Spec Coverage

| Spec requirement | Task |
|---|---|
| `spatie/laravel-passkeys` package | Task 1 |
| `passkeys` table created by migration | Task 1 |
| `HasPasskeys` trait on User model | Task 2 |
| `GET /settings/passkeys` route | Task 3 |
| Settings nav item | Task 3 |
| List existing passkeys | Task 4 |
| Add passkey (name + WebAuthn ceremony) | Task 4 + 6 |
| Remove passkey | Task 4 |
| Block last passkey removal when no password | Task 4 |
| `authenticateWithPasskey()` on Login | Task 5 |
| `confirmPasskeyAuth()` on Login | Task 5 |
| Passkey sign-in button on login page | Task 5 |
| JS base64url encoding/decoding | Task 6 |
| Alpine `passkeyRegister` component | Task 6 |
| Alpine `passkeyAuthenticate` component | Task 6 |
| `NotAllowedError` / `InvalidStateError` handling | Task 6 |
| Empty `allowCredentials` (discoverable) | Task 6 |
| Full test suite | Task 7 |
