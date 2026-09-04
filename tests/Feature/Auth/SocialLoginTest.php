<?php

use App\Models\SocialIdentity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Build the object Socialite hands back, including the raw provider payload that
 * the email-verified checks read.
 */
function socialiteUser(array $attributes = [], array $raw = []): SocialiteUser
{
    $user = new SocialiteUser;

    $user->setRaw($raw);

    return $user->map(array_merge([
        'id' => '1234567890',
        'nickname' => null,
        'name' => null,
        'email' => null,
        'avatar' => null,
    ], $attributes));
}

/**
 * Make Socialite::driver($provider)->user() return the given provider user.
 */
function fakeSocialiteUser(string $provider, SocialiteUser $user): void
{
    $driver = Mockery::mock(stdClass::class);
    $driver->shouldReceive('user')->andReturn($user);

    Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
}

function googleUser(?string $email, bool $verified, array $attributes = []): SocialiteUser
{
    return socialiteUser(
        array_merge(['email' => $email, 'name' => 'Provider Person'], $attributes),
        ['sub' => '1234567890', 'email' => $email, 'email_verified' => $verified],
    );
}

test('unknown provider slugs 404 rather than reaching socialite', function () {
    $this->get('/login/twitter')->assertNotFound();
    $this->get('/login/twitter/callback')->assertNotFound();
    $this->get('/login/apple')->assertNotFound();

    $this->assertGuest();
});

test('a provider that has not verified the email does not link to an existing account', function () {
    $victim = User::factory()->create([
        'email' => 'victim@example.com',
        'email_verified_at' => now(),
    ]);

    fakeSocialiteUser('google', googleUser('victim@example.com', verified: false));

    $response = $this->get('/login/google/callback');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');

    expect($victim->identities()->count())->toBe(0);
    expect(User::count())->toBe(1);
});

test('a verified provider email does not link to a local account that never verified it', function () {
    // The pre-account-takeover case: someone registered at the victim's address
    // before the victim ever signed in with the provider.
    $squatted = User::factory()->unverified()->create([
        'email' => 'victim@example.com',
    ]);

    fakeSocialiteUser('google', googleUser('victim@example.com', verified: true));

    $response = $this->get('/login/google/callback');

    $this->assertGuest();
    $response->assertRedirect(route('login'));

    expect($squatted->identities()->count())->toBe(0);
});

test('a verified provider email links to a verified existing account', function () {
    $user = User::factory()->create([
        'email' => 'owner@example.com',
        'email_verified_at' => now(),
    ]);

    fakeSocialiteUser('google', googleUser('owner@example.com', verified: true));

    $response = $this->get('/login/google/callback');

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');

    $this->assertDatabaseHas('social_identities', [
        'user_id' => $user->id,
        'provider_name' => 'google',
        'provider_id' => '1234567890',
    ]);

    expect(User::count())->toBe(1);
});

test('a null provider email does not match an existing account with no email', function () {
    $existing = User::create([
        'name' => 'Email-less OAuth user',
        'email' => null,
    ]);
    $existing->identities()->create([
        'provider_name' => 'discord',
        'provider_id' => 'some-other-discord-id',
    ]);

    fakeSocialiteUser('discord', socialiteUser(
        ['id' => 'new-discord-id', 'name' => 'Stranger', 'email' => null],
        ['id' => 'new-discord-id', 'username' => 'Stranger', 'verified' => true],
    ));

    $this->get('/login/discord/callback');

    expect(User::count())->toBe(2);
    $this->assertAuthenticated();
    expect(auth()->id())->not->toBe($existing->id);
});

test('a new user from a provider-verified email is created already verified', function () {
    // Nothing fires a Registered event on this path, so no verification mail would
    // ever be sent; the provider has already done the proving.
    fakeSocialiteUser('google', googleUser('newcomer@example.com', verified: true));

    $this->get('/login/google/callback')->assertRedirect('/dashboard');

    $user = User::sole();

    expect($user->email)->toBe('newcomer@example.com');
    expect($user->hasVerifiedEmail())->toBeTrue();
});

test('a new user from an unverified provider email is created unverified', function () {
    fakeSocialiteUser('google', googleUser('newcomer@example.com', verified: false));

    $this->get('/login/google/callback')->assertRedirect('/dashboard');

    $user = User::sole();

    expect($user->email)->toBe('newcomer@example.com');
    expect($user->hasVerifiedEmail())->toBeFalse();
});

test('a new user with no email at all is never marked verified', function () {
    fakeSocialiteUser('discord', socialiteUser(
        ['id' => 'anon', 'name' => 'Anon', 'email' => null],
        ['id' => 'anon', 'username' => 'Anon', 'verified' => true],
    ));

    $this->get('/login/discord/callback')->assertRedirect('/dashboard');

    $user = User::sole();

    expect($user->email)->toBeNull();
    expect($user->email_verified_at)->toBeNull();
});

test('an existing social identity logs its user in', function () {
    $user = User::factory()->create();
    $user->identities()->create([
        'provider_name' => 'github',
        'provider_id' => '1234567890',
    ]);

    fakeSocialiteUser('github', socialiteUser(
        ['nickname' => 'octocat', 'name' => 'The Octocat', 'email' => 'octocat@example.com'],
        ['id' => 1234567890, 'login' => 'octocat', 'email' => 'octocat@example.com'],
    ));

    $response = $this->get('/login/github/callback');

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');

    expect(SocialIdentity::count())->toBe(1);
});

test('an orphaned social identity does not blow up the callback', function () {
    // Rows created before the cascading foreign key existed outlived their user.
    // RefreshDatabase holds an open transaction, and SQLite ignores
    // `PRAGMA foreign_keys` inside one, so defer the check until a commit that
    // never comes.
    DB::statement('PRAGMA defer_foreign_keys = ON');

    SocialIdentity::create([
        'user_id' => 99999,
        'provider_name' => 'github',
        'provider_id' => '1234567890',
    ]);

    fakeSocialiteUser('github', socialiteUser(
        ['nickname' => 'octocat', 'name' => 'The Octocat', 'email' => 'octocat@example.com'],
        ['id' => 1234567890, 'login' => 'octocat', 'email' => 'octocat@example.com'],
    ));

    $response = $this->get('/login/github/callback');

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();

    // The orphan is replaced, not duplicated.
    expect(SocialIdentity::count())->toBe(1);
    expect(SocialIdentity::first()->user->email)->toBe('octocat@example.com');
});

test('deleting an account removes its social identities', function () {
    $user = User::factory()->create();
    $user->identities()->create([
        'provider_name' => 'github',
        'provider_id' => '1234567890',
    ]);

    $user->delete();

    expect(SocialIdentity::count())->toBe(0);
});

test('the same provider id from two providers can coexist', function () {
    $first = User::factory()->create();
    $first->identities()->create(['provider_name' => 'github', 'provider_id' => '42']);

    $second = User::factory()->create();
    $second->identities()->create(['provider_name' => 'google', 'provider_id' => '42']);

    expect(SocialIdentity::count())->toBe(2);
});

test('a provider user with no name falls back to something non-null', function () {
    fakeSocialiteUser('github', socialiteUser(
        ['nickname' => 'octocat', 'name' => null, 'email' => 'octocat@example.com'],
        ['id' => 1234567890, 'login' => 'octocat', 'email' => 'octocat@example.com'],
    ));

    $this->get('/login/github/callback')->assertRedirect('/dashboard');

    $this->assertDatabaseHas('users', [
        'email' => 'octocat@example.com',
        'name' => 'octocat',
    ]);
});

test('a provider user with neither name nor nickname nor email still gets a name', function () {
    fakeSocialiteUser('discord', socialiteUser(
        ['id' => 'anon', 'name' => null, 'nickname' => null, 'email' => null],
        ['id' => 'anon', 'verified' => false],
    ));

    $this->get('/login/discord/callback')->assertRedirect('/dashboard');

    $user = User::sole();

    expect($user->name)->not->toBeEmpty();
    expect($user->email)->toBeNull();
});

test('a failed callback redirects with a message instead of bouncing silently', function () {
    $driver = Mockery::mock(stdClass::class);
    $driver->shouldReceive('user')->andThrow(new InvalidStateException);

    Socialite::shouldReceive('driver')->with('google')->andReturn($driver);

    $response = $this->get('/login/google/callback');

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');

    expect(session('status'))->toContain('Google');
});

test('a failed redirect to the provider reports the failure', function () {
    Socialite::shouldReceive('driver')
        ->with('github')
        ->andThrow(new InvalidArgumentException('Missing client secret.'));

    $response = $this->get('/login/github');

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
});
