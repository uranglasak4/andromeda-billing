<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login
     */
    public function showLogin()
    {
        // Fitur: Jika user sudah login, cek dulu status keaktifannya
        if (Auth::check()) {
            if (!Auth::user()->is_active) {
                Auth::logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Owner/Manager.',
                ]);
            }

            if (Auth::user()->role == 'master') {
                return redirect()->route('master.dashboard');
            }
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Proses login
     */
    public function login(Request $request)
    {
        // Validasi input
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        // 1. Cek keberadaan user & status keaktifannya terlebih dahulu
        $user = User::where('username', $request->username)->first();

        if ($user && !$user->is_active) {
            return back()->withErrors([
                'username' => 'Akun Anda telah dinonaktifkan. Silakan hubungi Owner/Manager.',
            ])->onlyInput('username');
        }

        // 2. Coba Login jika akun aktif
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Cek Role dan arahkan ke Dashboard yang sesuai
            if (Auth::user()->role == 'master') {
                return redirect()->intended(route('master.dashboard'));
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        // Jika Gagal (password salah/user tidak ada), balikkan ke login dengan pesan error
        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    /**
     * Proses Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Setelah logout, lempar ke halaman login
        return redirect()->route('login');
    }
}
