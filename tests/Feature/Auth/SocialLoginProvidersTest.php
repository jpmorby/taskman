<?php

use App\Livewire\Auth\Login;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

beforeEach(function () {
    config([
        'services.github.client_id' => 'github-id',
        'services.google.client_id' => 'google-id',
        'services.discord.client_id' => 'discord-id',
        'services.apple.client_id' => 'apple-id',
        'services.apple.enabled' => false,
    ]);
});

test('apple is off by default even when it is fully configured', function () {
    config(['services.apple.enabled' => false]);

    expect(Login::enabledProviders())->not->toContain('apple');

    $this->get('/login')->assertOk()->assertDontSee('/login/apple', false);
    $this->get('/register')->assertOk()->assertDontSee('/login/apple', false);
    $this->get('/login/apple')->assertNotFound();
    $this->get('/login/apple/callback')->assertNotFound();
    $this->post('/login/apple/callback')->assertNotFound();
});

test('apple is shown once it is enabled and has a client id', function () {
    config(['services.apple.enabled' => true]);

    expect(Login::enabledProviders())->toContain('apple');

    $this->get('/login')->assertOk()->assertSee('/login/apple', false);
    $this->get('/register')->assertOk()->assertSee('/login/apple', false);
});

test('enabling apple without a client id does not show a broken button', function () {
    config([
        'services.apple.enabled' => true,
        'services.apple.client_id' => null,
    ]);

    expect(Login::enabledProviders())->not->toContain('apple');

    $this->get('/login')->assertOk()->assertDontSee('/login/apple', false);
    $this->get('/login/apple')->assertNotFound();
});

test('the existing providers stay on unless switched off in config', function () {
    expect(Login::enabledProviders())->toBe(['github', 'google', 'discord']);

    config(['services.discord.enabled' => false]);

    expect(Login::enabledProviders())->toBe(['github', 'google']);

    $this->get('/login')->assertOk()
        ->assertSee('/login/github', false)
        ->assertDontSee('/login/discord', false);
    $this->get('/login/discord')->assertNotFound();
});

test('with no provider enabled the login page shows no social buttons', function () {
    foreach (Login::PROVIDERS as $provider) {
        config(["services.{$provider}.enabled" => false]);
    }

    $this->get('/login')->assertOk()
        ->assertDontSee('/login/github', false)
        ->assertDontSee('/login/apple', false);
});

test('the apple callback route accepts the form POST apple sends', function () {
    config(['services.apple.enabled' => true]);

    // Reaches the controller and fails on Socialite's state check, as a forged
    // callback should.
    $this->post('/login/apple/callback', ['code' => 'x', 'state' => 'forged'])
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

// CSRF verification is skipped under test, so a request cannot prove this: check
// the exemption is registered. Apple's cross-site POST can never carry a token.
test('only the apple callback is exempt from csrf verification', function () {
    expect(app(ValidateCsrfToken::class)->getExcludedPaths())
        ->toBe(['login/apple/callback']);
});
