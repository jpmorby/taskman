<?php

use App\Facades\Currency;
use App\Support\CurrencyManager;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

beforeEach(function () {
    Session::forget(CurrencyManager::SESSION_KEY);
    App::setLocale('en');
});

test('it guesses a currency from the language part of a locale', function () {
    expect(Currency::guess('fr'))->toBe('EUR')
        ->and(Currency::guess('ru'))->toBe('EUR')
        ->and(Currency::guess('en'))->toBe('GBP');
});

test('it prefers the most specific locale match', function () {
    expect(Currency::guess('en_US'))->toBe('USD')
        ->and(Currency::guess('en-US'))->toBe('USD')
        ->and(Currency::guess('fr_CA'))->toBe('CAD')
        ->and(Currency::guess('de_CH'))->toBe('CHF');
});

test('it falls back to the default currency for an unknown locale', function () {
    config()->set('currency.default', 'USD');

    expect(Currency::guess('xx-pirate'))->toBe('USD');
});

test('it falls back to a supported currency when the default is unknown', function () {
    config()->set('currency.default', 'ZZZ');

    expect(Currency::default())->toBe('GBP');
});

test('it ignores an unsupported currency held in the session', function () {
    App::setLocale('fr');
    Session::put(CurrencyManager::SESSION_KEY, 'ZZZ');

    expect(Currency::override())->toBeNull()
        ->and(Currency::hasOverride())->toBeFalse()
        ->and(Currency::current())->toBe('EUR');
});

test('forgetting the override falls back to the guess', function () {
    App::setLocale('fr');
    Currency::store('USD');

    expect(Currency::current())->toBe('USD');

    Currency::forget();

    expect(Currency::hasOverride())->toBeFalse()
        ->and(Currency::current())->toBe('EUR');
});

test('setCurrent does not persist the currency to the session', function () {
    Currency::setCurrent('JPY');

    expect(Currency::current())->toBe('JPY')
        ->and(Session::has(CurrencyManager::SESSION_KEY))->toBeFalse();
});

test('it exposes the symbol and translated name of a currency', function () {
    expect(Currency::symbol('GBP'))->toBe('£')
        ->and(Currency::symbol('EUR'))->toBe('€')
        ->and(Currency::name('USD'))->toBe('US Dollar');
});

test('it formats amounts using the settings of the currency', function () {
    expect(Currency::format(1234.5, 'GBP'))->toBe('£1,234.50')
        ->and(Currency::format(1234.5, 'EUR'))->toBe('€1.234,50')
        ->and(Currency::format(1234.5, 'JPY'))->toBe('¥1,235')
        ->and(Currency::format(1234.5, 'CHF'))->toBe("CHF1'234.50")
        ->and(Currency::format(-99, 'USD'))->toBe('-$99.00');
});

test('it formats amounts in the current currency by default', function () {
    Currency::store('USD');

    expect(Currency::format(10))->toBe('$10.00');
});

test('it lists the supported currency codes', function () {
    expect(Currency::codes())->toContain('GBP', 'EUR', 'USD')
        ->and(Currency::isSupported('gbp'))->toBeTrue()
        ->and(Currency::isSupported('ZZZ'))->toBeFalse()
        ->and(Currency::isSupported(null))->toBeFalse();
});
