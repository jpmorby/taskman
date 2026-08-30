<?php

/**
 * (C) Jon Morby 2025.  All Rights Reserved.
 */

namespace App\Http\Middleware;

use App\Support\CurrencyManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This middleware starts each request with a clean currency, so the currency
 * one visitor pinned cannot be carried into the next request where the
 * container is reused.
 *
 * The currency itself is resolved when it is first needed: the choice stored
 * in the session by the currency toggle always wins, and otherwise it is
 * guessed from the locale LanguageMiddleware has set.
 */
class CurrencyMiddleware
{
    public function __construct(protected CurrencyManager $currency) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->currency->flush();

        return $next($request);
    }
}
