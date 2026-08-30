<?php

/**
 * (C) Jon Morby 2025.  All Rights Reserved.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The currency used when nothing better is known about the visitor: no
    | currency has been chosen in the session and the active locale does not
    | map to one of the supported currencies below.
    |
    */

    'default' => env('APP_CURRENCY', 'GBP'),

    /*
    |--------------------------------------------------------------------------
    | Supported Currencies
    |--------------------------------------------------------------------------
    |
    | The currencies offered by the currency toggle. Each entry is keyed by its
    | ISO 4217 code and describes how amounts in that currency are rendered.
    | The "name" is passed through the translator, so it must also exist as a
    | key in the language files.
    |
    */

    'supported' => [
        'GBP' => [
            'name' => 'British Pound',
            'symbol' => '£',
            'position' => 'before',
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ],
        'EUR' => [
            'name' => 'Euro',
            'symbol' => '€',
            'position' => 'before',
            'decimals' => 2,
            'decimal_separator' => ',',
            'thousands_separator' => '.',
        ],
        'USD' => [
            'name' => 'US Dollar',
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ],
        'CAD' => [
            'name' => 'Canadian Dollar',
            'symbol' => 'CA$',
            'position' => 'before',
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ],
        'AUD' => [
            'name' => 'Australian Dollar',
            'symbol' => 'A$',
            'position' => 'before',
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ],
        'CHF' => [
            'name' => 'Swiss Franc',
            'symbol' => 'CHF',
            'position' => 'before',
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => "'",
        ],
        'JPY' => [
            'name' => 'Japanese Yen',
            'symbol' => '¥',
            'position' => 'before',
            'decimals' => 0,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Locale Guesses
    |--------------------------------------------------------------------------
    |
    | Maps an application locale onto the currency we assume the visitor wants
    | until they tell us otherwise. A choice stored in the session always wins
    | over these guesses. Keys may be a bare language ("fr") or a full locale
    | ("fr_CA"); the more specific match is preferred.
    |
    */

    'locales' => [
        'en' => 'GBP',
        'en_GB' => 'GBP',
        'en_US' => 'USD',
        'en_CA' => 'CAD',
        'en_AU' => 'AUD',
        'de' => 'EUR',
        'de_CH' => 'CHF',
        'es' => 'EUR',
        'fr' => 'EUR',
        'fr_CA' => 'CAD',
        'fr_CH' => 'CHF',
        'it' => 'EUR',
        'it_CH' => 'CHF',
        'pt' => 'EUR',
        'ru' => 'EUR',
        'ja' => 'JPY',
    ],

];
