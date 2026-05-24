<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $role)
    {
        // 1. Jika belum login, tendang ke halaman login
        if (! auth()->check()) {
            return redirect('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // 2. JIKA SALING SILANG AKSES (User mengakses rute yang bukan hak role-nya)
        if (auth()->user()->role !== $role) {
            // Kita cek role aslinya dia apa, lalu redirect paksa ke dashboard yang sesuai!
            if (auth()->user()->role === 'master') {
                return redirect()->route('master.dashboard');
            } elseif (auth()->user()->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
        }

        return $next($request);
    }
}
