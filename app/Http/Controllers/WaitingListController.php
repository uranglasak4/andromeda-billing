<?php

namespace App\Http\Controllers; // Pastikan ini benar

use Illuminate\Http\Request;
use App\Models\WaitingList; // Sesuaikan dengan nama model Anda

class WaitingListController extends Controller
{
    public function store(Request $request)
{
    // Validasi data yang masuk
    $request->validate([
        'nama_pelanggan' => 'required|string|max:255',
    ]);

    // Simpan ke database menggunakan nama kolom yang benar: customer_name
    \App\Models\WaitingList::create([
        'customer_name' => $request->nama_pelanggan,
        'status' => 'waiting', // Menambahkan status default jika diperlukan
    ]);

    

    return redirect()->back()->with('success', 'Antrean berhasil ditambahkan!');
}
}