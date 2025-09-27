<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AuthenticationService;
use App\Http\Integrations\TelconApiConnector;

class AuthenticationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TelconApiConnector::class, function ($app) {
            return new TelconApiConnector();
        });

        $this->app->singleton(AuthenticationService::class, function ($app) {
            return new AuthenticationService($app->make(TelconApiConnector::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
