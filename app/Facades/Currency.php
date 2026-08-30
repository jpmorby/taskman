<?php

/**
 * (C) Jon Morby 2025.  All Rights Reserved.
 */

namespace App\Facades;

use App\Support\CurrencyManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array<string, array<string, mixed>> supported()
 * @method static array<int, string> codes()
 * @method static bool isSupported(?string $code)
 * @method static string normalise(string $code)
 * @method static string default()
 * @method static string guess(?string $locale = null)
 * @method static string|null override()
 * @method static bool hasOverride()
 * @method static string current()
 * @method static void setCurrent(string $code)
 * @method static void flush()
 * @method static void store(string $code)
 * @method static void forget()
 * @method static array<string, mixed> definition(?string $code = null)
 * @method static string symbol(?string $code = null)
 * @method static string name(?string $code = null)
 * @method static string format(int|float $amount, ?string $code = null)
 *
 * @see CurrencyManager
 */
class Currency extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CurrencyManager::class;
    }
}
