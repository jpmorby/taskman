<?php

use App\Livewire\Actions\Logout;
use App\Livewire\Settings\DeleteUserForm;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

/**
 * DeleteUserForm demanded current_password unconditionally, which no OAuth-only or
 * passkey-only account can satisfy: their password column is null.
 */
test('a user with no password can delete their own account', function () {
    $user = User::create([
        'name' => 'OAuth Only',
        'email' => 'oauth-only@example.com',
        'email_verified_at' => now(),
    ]);
    $user->identities()->create([
        'provider_name' => 'github',
        'provider_id' => '1234567890',
    ]);

    $this->actingAs($user);

    $logout = Mockery::mock(Logout::class);
    $logout->shouldReceive('__invoke')->andReturn(fn () => Auth::user());
    $this->app->instance(Logout::class, $logout);

    Livewire::test(DeleteUserForm::class)
        ->call('deleteUser')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('social_identities', ['user_id' => $user->id]);
});

test('a user with a password still has to supply it', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong-password')
        ->call('deleteUser')
        ->assertHasErrors(['password' => 'current_password']);

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});
