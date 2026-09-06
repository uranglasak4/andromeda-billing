<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // 1. Tampilkan Halaman Daftar Akun (Urutan: OWNER -> MASTER -> ADMIN)
    public function index()
    {
        $users = User::orderByRaw("
            CASE
                WHEN id = 1 THEN 1
                WHEN role = 'master' THEN 2
                WHEN role = 'admin' THEN 3
                ELSE 4
            END ASC
        ")->orderBy('name', 'asc')->paginate(10);

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

        // PROTEKSI HIERARKI: Jika yang login BUKAN Owner (ID 1), dilarang buat akun ber-role Master
        if (auth()->id() !== 1 && $request->role === 'master') {
            return back()->with('error', 'Gagal! Hanya Owner Utama yang berhak membuat akun Master baru.');
        }

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password), // Enkripsi password otomatis
            'role' => $request->role,
            'is_active' => true, // Akun baru otomatis aktif
        ]);

        return back()->with('success', 'Akun staff baru berhasil ditambahkan!');
    }

    // 3. Update Akun (Nama, Username, Role, Status & Opsional Password)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $currentUserId = auth()->id();

        // PROTEKSI HIERARKI: Master biasa (Bukan ID 1) dilarang mengedit/reset password sesama Master lain
        if ($currentUserId !== 1 && $user->role === 'master' && $user->id !== $currentUserId) {
            return back()->with('error', 'Gagal! Anda tidak memiliki wewenang untuk mengubah data akun Master ini.');
        }

        // PROTEKSI HIERARKI: Master biasa (Bukan ID 1) dilarang mengubah role siapapun jadi Master
        if ($currentUserId !== 1 && $request->role === 'master' && $user->role !== 'master') {
            return back()->with('error', 'Gagal! Hanya Owner Utama yang dapat menetapkan role Master.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $id,
            'role' => 'required|in:master,admin',
            'password' => 'nullable|string|min:4', // Password boleh kosong jika tidak ingin diubah
            'is_active' => 'required|boolean',
        ]);

        // Proteksi mutlak: Akun Owner Utama (ID 1) tidak boleh dinonaktifkan
        $isActiveStatus = ($user->id === 1) ? true : $request->is_active;

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'role' => $request->role,
            'is_active' => $isActiveStatus,
        ];

        // Jika input password diisi, enkripsi dan masukkan ke array update
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return back()->with('success', 'Data akun berhasil diperbarui!');
    }

    // 4. Toggle Status Aktif / Nonaktif Langsung dari Tabel
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $currentUserId = auth()->id();

        // Proteksi Owner Utama
        if ($user->id === 1) {
            return back()->with('error', 'Gagal! Akun Owner Utama tidak dapat dinonaktifkan.');
        }

        // Proteksi Hierarki: Master biasa tidak boleh ubah status Master lain
        if ($currentUserId !== 1 && $user->role === 'master') {
            return back()->with('error', 'Gagal! Hanya Owner Utama yang berhak menonaktifkan akun Master.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $statusText = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun {$user->name} berhasil {$statusText}!");
    }

    // 5. Hapus Akun Staff
    public function destroy($id)
    {
        $targetUser = User::findOrFail($id);
        $currentUser = auth()->user();

        // 1. ATURAN UTAMA: Tidak bisa menghapus akun yang sedang digunakan (diri sendiri)
        if ($currentUser->id == $targetUser->id) {
            return back()->with('error', 'Gagal! Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // 2. ATURAN HIERARKI: Hanya Master yang bisa menghapus user
        if ($currentUser->role !== 'master') {
            return back()->with('error', 'Akses ditolak! Hanya role Master yang memiliki wewenang menghapus akun.');
        }

        // 3. PROTEKSI OWNER UTAMA (ID 1): Hanya Owner (ID 1) yang bisa menghapus akun ber-role Master
        if ($targetUser->role === 'master' && $currentUser->id !== 1) {
            return back()->with('error', 'Gagal! Hanya Owner Utama yang dapat menghapus akun ber-role Master.');
        }

        // 4. PROTEKSI MASTER TERAKHIR: Mencegah sistem kehabisan akun Master
        if ($targetUser->role === 'master') {
            $totalMaster = User::where('role', 'master')->count();
            if ($totalMaster <= 1) {
                return back()->with('error', 'Gagal! Harus tersisa minimal satu akun Master di dalam sistem.');
            }
        }

        $targetUser->delete();

        return back()->with('success', 'Akun ' . $targetUser->name . ' berhasil dihapus!');
    }
}
