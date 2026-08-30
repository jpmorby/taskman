<?php

use App\Facades\Currency;
use App\Livewire\CurrencyMenu;
use App\Models\User;
use App\Support\CurrencyManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;

beforeEach(function () {
    Session::forget(CurrencyManager::SESSION_KEY);
    App::setLocale('en');
});

test('currency menu component can be rendered', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
    $response->assertSeeLivewire(CurrencyMenu::class);
});

test('currency menu uses the currency stored in the session', function () {
    Session::put(CurrencyManager::SESSION_KEY, 'JPY');

    $component = Livewire::test(CurrencyMenu::class);

    expect($component->get('currency'))->toBe('JPY');
});

test('currency menu guesses from the locale when nothing is stored', function () {
    App::setLocale('de');

    $component = Livewire::test(CurrencyMenu::class);

    expect($component->get('currency'))->toBe('EUR');
});

test('a stored currency overrides the locale guess', function () {
    App::setLocale('de');
    Session::put(CurrencyManager::SESSION_KEY, 'USD');

    expect(Currency::guess())->toBe('EUR')
        ->and(Currency::current())->toBe('USD');
});

test('setCurrency stores the chosen currency', function () {
    $component = Livewire::test(CurrencyMenu::class)
        ->call('setCurrency', 'USD');

    expect($component->get('currency'))->toBe('USD')
        ->and(Session::get(CurrencyManager::SESSION_KEY))->toBe('USD')
        ->and(Currency::current())->toBe('USD');
});

test('setCurrency accepts a lower case code', function () {
    Livewire::test(CurrencyMenu::class)
        ->call('setCurrency', 'eur');

    expect(Session::get(CurrencyManager::SESSION_KEY))->toBe('EUR');
});

test('setCurrency dispatches currency-changed event', function () {
    Livewire::test(CurrencyMenu::class)
        ->call('setCurrency', 'CHF')
        ->assertDispatched('currency-changed', 'CHF');
});

test('setCurrency ignores an unsupported currency', function () {
    Livewire::test(CurrencyMenu::class)
        ->call('setCurrency', 'XYZ')
        ->assertNotDispatched('currency-changed');

    expect(Session::has(CurrencyManager::SESSION_KEY))->toBeFalse();
});

test('setCurrency redirects back to the referrer', function () {
    $this->withHeaders(['Referer' => 'http://localhost/dashboard']);

    Livewire::test(CurrencyMenu::class)->call('setCurrency', 'AUD');

    expect(Session::get(CurrencyManager::SESSION_KEY))->toBe('AUD');
});
