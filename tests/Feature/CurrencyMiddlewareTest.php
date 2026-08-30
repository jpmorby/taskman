<?php

use App\Facades\Currency;
use App\Support\CurrencyManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->get('/__currency-probe', function () {
        return Currency::current().'|'.(Currency::hasOverride() ? 'override' : 'guess');
    });
});

test('a request without a stored currency uses the locale guess', function () {
    App::setLocale('de');

    $this->get('/__currency-probe')->assertSee('EUR|guess');
});

test('a request with a stored currency uses it instead of the guess', function () {
    App::setLocale('de');

    $this->withSession([CurrencyManager::SESSION_KEY => 'JPY'])
        ->get('/__currency-probe')
        ->assertSee('JPY|override');
});

test('a currency pinned for one request does not leak into the next', function () {
    Currency::setCurrent('JPY');

    expect(Currency::current())->toBe('JPY');

    App::setLocale('en');
    $this->get('/__currency-probe')->assertSee('GBP|guess');
});
