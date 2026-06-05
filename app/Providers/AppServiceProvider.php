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
        // 1. Deteksi otomatis jika website sedang dibuka lewat link ngrok
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            \URL::forceScheme('https');
        }

        // 2. Paksa URL root Laravel mengikuti domain yang sedang aktif di browser secara dinamis
        if (request()->server('HTTP_X_FORWARDED_HOST')) {
            \URL::forceRootUrl('https://' . request()->server('HTTP_X_FORWARDED_HOST'));
        }
    }
}
