<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

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
        // Paginator tetap biarkan jika ada
        if (class_exists(\Illuminate\Pagination\Paginator::class)) {
            \Illuminate\Pagination\Paginator::useBootstrapFive();
        }

        // PERBAIKAN: Menggunakan getHost() untuk mendeteksi domain Ngrok
        if (str_contains(request()->getHost(), 'ngrok-free') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            URL::forceScheme('https');
        }
    }
}
