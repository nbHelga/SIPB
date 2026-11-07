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
        // Set konfigurasi global Midtrans
        // \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        // \Midtrans\Config::$isProduction = config('services.midtrans.is_production');
        // \Midtrans\Config::$isSanitized = true;
        // \Midtrans\Config::$is3ds = true;
    }
}
