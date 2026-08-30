<?php

namespace App\Providers;

use App\Support\CurrencyManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CurrencyManager::class, function ($app) {
            return new CurrencyManager($app['config'], $app['session.store']);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
