<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPasskeys\Database\Factories\PasskeyFactory;

uses(RefreshDatabase::class);

it('user has a passkeys relationship', function () {
    $user = User::factory()->create();

    expect($user->passkeys())->toBeInstanceOf(HasMany::class);
});

it('user can have multiple passkeys', function () {
    $user = User::factory()->create();

    PasskeyFactory::new()
        ->for($user, 'authenticatable')
        ->create(['name' => 'MacBook']);

    PasskeyFactory::new()
        ->for($user, 'authenticatable')
        ->create(['name' => 'iPhone']);

    expect($user->passkeys()->count())->toBe(2);
});

it('settings/passkeys route requires authentication', function () {
    $response = $this->get(route('settings.passkeys'));

    $response->assertRedirect(route('login'));
});

it('authenticated user can access settings/passkeys', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.passkeys'));

    $response->assertOk();
});
