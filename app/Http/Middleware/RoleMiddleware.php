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
        if (! auth()->check()) {
            return redirect('/')->with('error', 'Silakan login terlebih dahulu.');
        }

        if (auth()->user()->role !== $role) {
            // Jika admin coba masuk ke owner, atau sebaliknya
            return abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
