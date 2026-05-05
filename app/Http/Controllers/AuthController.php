<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login
     */
    public function showLogin()
    {
        // Fitur: Jika user sudah login, jangan kasih masuk ke halaman login lagi
        if (Auth::check()) {
            if (Auth::user()->role == 'owner') {
                return redirect()->route('owner.dashboard');
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

        // Coba Login
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Cek Role dan arahkan ke Dashboard yang sesuai
            if (Auth::user()->role == 'owner') {
                return redirect()->intended(route('owner.dashboard'));
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        // Jika Gagal, balikkan ke login dengan pesan error
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