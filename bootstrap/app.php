<?php

use App\Http\Middleware\CurrencyMiddleware;
use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CurrencyMiddleware follows LanguageMiddleware: the currency we show
        // a visitor is guessed from the locale that has just been set.
        $middleware->append([LanguageMiddleware::class, CurrencyMiddleware::class]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
