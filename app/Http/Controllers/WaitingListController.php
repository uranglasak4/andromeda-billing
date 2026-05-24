<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaitingList;

class WaitingListController extends Controller
{
    public function store(Request $request)
    {
        // Validasi input nama dan nomor whatsapp dari form customer
        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'nomor_wa'       => 'required|numeric',
        ]);

        // Simpan ke database menggunakan kolom properti model Anda ($fillable)
        \App\Models\WaitingList::create([
            'customer_name' => $request->nama_pelanggan,
            'phone_number'  => $request->nomor_wa, // Menyimpan no wa ke field phone_number database
            'status'        => 'waiting',
        ]);

        return redirect()->back()->with('success', 'Antrean berhasil ditambahkan! Silakan tunggu giliran.');
    }
}
