<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Support\Facades\Event;

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

        // 3. Catat waktu login terakhir setiap ada user yang login
        Event::listen(Authenticated::class, function ($event) {
            if ($event->user) {
                $event->user->timestamps = false; // Mencegah kolom updated_at ikut berubah saat login
                $event->user->update(['last_login_at' => now()]);
            }
        });
    }
}
