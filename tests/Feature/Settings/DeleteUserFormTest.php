<?php

use App\Livewire\Settings\DeleteUserForm;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('delete user form component can be rendered', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/settings');
    $response->assertStatus(200);
    $response->assertSeeLivewire(DeleteUserForm::class);
});

test('delete user form requires password', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Try to delete with empty password
    Livewire::test(DeleteUserForm::class)
        ->set('password', '')
        ->call('deleteUser')
        ->assertHasErrors(['password' => 'required']);
});

test('delete user form requires correct password', function () {
    $password = 'password123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $this->actingAs($user);

    // Try to delete with incorrect password
    Livewire::test(DeleteUserForm::class)
        ->set('password', 'wrong_password')
        ->call('deleteUser')
        ->assertHasErrors(['password' => 'current_password']);
});

test('user can delete their account with correct password', function () {
    $password = 'password123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $this->actingAs($user);

    // Verify user exists
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
    ]);

    // Mock the Logout action to prevent actual logout during test
    $logoutMock = Mockery::mock(App\Livewire\Actions\Logout::class);
    $logoutMock->shouldReceive('__invoke')->andReturn(function() {
        return Auth::user();
    });
    $this->app->instance(App\Livewire\Actions\Logout::class, $logoutMock);

    // Delete the user with correct password
    Livewire::test(DeleteUserForm::class)
        ->set('password', $password)
        ->call('deleteUser');

    // Verify user was deleted
    $this->assertDatabaseMissing('users', [
        'id' => $user->id,
    ]);
});

test('delete user form redirects to home page after deletion', function () {
    $password = 'password123';
    $user = User::factory()->create([
        'password' => Hash::make($password),
    ]);
    $this->actingAs($user);

    // Mock the Logout action to prevent actual logout during test
    $logoutMock = Mockery::mock(App\Livewire\Actions\Logout::class);
    $logoutMock->shouldReceive('__invoke')->andReturn(function() {
        return Auth::user();
    });
    $this->app->instance(App\Livewire\Actions\Logout::class, $logoutMock);

    // Delete the user and check redirect
    $component = Livewire::test(DeleteUserForm::class)
        ->set('password', $password)
        ->call('deleteUser');
    
    // In Livewire's test environment, we can't directly assert the redirect,
    // but we can check that the redirect method was called with the expected path
    expect($component->effects['redirect']['path'])->toBe('/');
    expect($component->effects['redirect']['navigate'])->toBeTrue();
});