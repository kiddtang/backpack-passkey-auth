<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Customize Controllers
        $this->app->bind(
            \Backpack\CRUD\app\Http\Controllers\MyAccountController::class,
            \App\Http\Controllers\Admin\MyAccountController::class
        );

        $this->app->bind(
            \Backpack\CRUD\app\Http\Controllers\Auth\LoginController::class,
            \App\Http\Controllers\Admin\Auth\LoginController::class
        );
    }
}
