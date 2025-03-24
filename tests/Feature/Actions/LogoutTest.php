<?php

use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

test('logout action logs the user out', function () {
    // Create and login a user
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Verify user is logged in
    expect(Auth::check())->toBeTrue();
    
    // Execute the logout action
    $logout = new Logout();
    $response = $logout();
    
    // Verify user is logged out
    expect(Auth::check())->toBeFalse();
});

test('logout action invalidates session', function () {
    // Create and login a user
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Put something in the session
    Session::put('test_key', 'test_value');
    expect(Session::has('test_key'))->toBeTrue();
    
    // Execute the logout action
    $logout = new Logout();
    $response = $logout();
    
    // Verify session was invalidated
    expect(Session::has('test_key'))->toBeFalse();
});

test('logout action regenerates token', function () {
    // Create and login a user
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Get the initial token
    $initialToken = Session::token();
    
    // Execute the logout action with mocking to prevent the redirect
    $logout = new Logout();
    
    // Use reflection to access the private method without executing the redirect
    $reflectionMethod = new ReflectionMethod(Logout::class, '__invoke');
    $reflectionMethod->setAccessible(true);
    $reflectionMethod->invoke($logout);
    
    // Verify token was regenerated
    expect(Session::token())->not->toBe($initialToken);
});

test('logout action redirects to home page', function () {
    // Create and login a user
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Execute the logout action
    $logout = new Logout();
    $response = $logout();

    // Verify redirect - check just the path in case of domain differences
    $url = $response->getTargetUrl();
    $path = parse_url($url, PHP_URL_PATH);
    expect($path)->toBe('/');
});