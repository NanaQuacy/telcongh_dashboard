<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Blade::if('canuser', function ($permission) {
            return in_array($permission, session('permissions', []));
        });
        
        Blade::if('roleuser', function ($role) {
            return in_array($role, session('roles', []));
        });
    }
}
