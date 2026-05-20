<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 1. Tampilkan Halaman Daftar Akun
    public function index()
    {
        // Menampilkan 10 user per halaman, diurutkan dari yang terbaru
        $users = User::orderBy('created_at', 'desc')->paginate(10);
        return view('master.users', compact('users'));
    }

    // 2. Simpan Akun Baru
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:4',
            'role' => 'required|in:master,admin',
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password), // Enkripsi password otomatis
            'role' => $request->role,
        ]);

        return back()->with('success', 'Akun staff baru berhasil ditambahkan!');
    }

    // 3. Update Akun (Nama, Username, Role, & Opsional Password)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'role' => 'required|in:master,admin',
            'password' => 'nullable|string|min:4', // Password boleh kosong jika tidak ingin diubah
        ]);

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'role' => $request->role,
        ];

        // Jika input password diisi, enkripsi dan masukkan ke array update
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'Data akun berhasil diperbarui!');
    }

    // 4. Hapus Akun Staff
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Proteksi: Mencegah akun master menghapus dirinya sendiri secara tidak sengaja
        if (auth()->id() == $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif!');
        }

        $user->delete();
        return back()->with('success', 'Akun staff berhasil dihapus!');
    }
}
