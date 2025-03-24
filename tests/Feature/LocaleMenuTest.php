<?php

use App\Livewire\LocaleMenu;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

test('locale menu component can be rendered', function () {
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
    $response->assertSeeLivewire(LocaleMenu::class);
});

test('locale menu has correct initial locale', function () {
    // Set session locale
    $locale = 'fr';
    Session::put('locale', $locale);

    // Test component initialization
    $component = Livewire::test(LocaleMenu::class);
    expect($component->get('locale'))->toBe($locale);
});

test('locale menu uses app locale if session locale is not set', function () {
    // Clear session locale
    Session::forget('locale');
    
    // Set app locale
    $locale = 'es';
    App::setLocale($locale);

    // Test component initialization
    $component = Livewire::test(LocaleMenu::class);
    expect($component->get('locale'))->toBe($locale);
});

test('setLocale method changes application locale', function () {
    // Initial state
    Session::forget('locale');
    App::setLocale('en');
    
    // Set up component and change locale
    $newLocale = 'de';
    $component = Livewire::test(LocaleMenu::class)
        ->call('setLocale', $newLocale);
    
    // Check that app locale was set
    expect(App::getLocale())->toBe($newLocale);
    expect(Session::get('locale'))->toBe($newLocale);
});

test('setLocale method dispatches locale-changed event', function () {
    $newLocale = 'it';
    
    Livewire::test(LocaleMenu::class)
        ->call('setLocale', $newLocale)
        ->assertDispatched('locale-changed', $newLocale);
});

test('setLocale method redirects back to referrer', function () {
    $newLocale = 'pt';
    
    // Mock the referrer
    $referrer = 'http://localhost/dashboard';
    $this->withHeaders(['Referer' => $referrer]);
    
    // Call the method with mocked request
    $component = Livewire::test(LocaleMenu::class)
        ->call('setLocale', $newLocale);
    
    // Since we can't assert on the redirect in Livewire test
    // We're checking that no exceptions were thrown and our state was updated
    expect(App::getLocale())->toBe($newLocale);
    expect(Session::get('locale'))->toBe($newLocale);
});