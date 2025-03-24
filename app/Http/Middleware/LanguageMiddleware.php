<?php

/**
 * (C) Jon Morby 2025.  All Rights Reserved.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * This middleware sets the application locale based on the session.
 */
class LanguageMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $locale = Session::get('locale', App::getLocale());
        App::setLocale($locale);

        return $next($request);
    }
}
