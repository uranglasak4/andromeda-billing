<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PoolTable;
use App\Models\Transaction;
use App\Models\Package;

class BillingController extends Controller
{
    public function openTable(Request $request, $id)
{
    $table = PoolTable::findOrFail($id);
    $startTime = now();
    $endTime = null;
    $duration = null;
    $billingType = 'personal';
    $statusMeja = 'playing'; // Default merah

    if ($request->duration === 'manual') {
        $billingType = 'hourly';
        $duration = $request->manual_hours * 60;
        $endTime = $startTime->copy()->addMinutes($duration);
    } elseif ($request->duration === 'personal') {
        $billingType = 'personal';
        $statusMeja = 'personal'; // Set status khusus agar warna kuning
        $endTime = null;
    } else {
        $billingType = 'package';
        $duration = (int) $request->duration;
        $endTime = $startTime->copy()->addMinutes($duration);
    }

    $table->update(['status' => $statusMeja]); // Update ke 'playing' atau 'personal'

    Transaction::create([
        'user_id' => auth()->id() ?? 1,
        'pool_table_id' => $table->id,
        'customer_name' => $request->customer_name,
        'billing_type' => $billingType,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'duration' => $duration,
        'status' => 'running',
    ]);

    return back()->with('success', 'Meja ' . $table->table_number . ' dimulai!');
}
    public function moveTable(Request $request)
{
    // Cari data meja asal dan tujuan[cite: 27]
    $fromTable = PoolTable::findOrFail($request->from_table_id);
    $toTable = PoolTable::findOrFail($request->to_table_id);

    // Cari transaksi aktif (running) di meja asal
    $transaction = Transaction::where('pool_table_id', $fromTable->id)
                                ->where('status', 'running')
                                ->first();

    if ($transaction) {
        // 1. Pindahkan status meja asal ke meja tujuan[cite: 27]
        $toTable->update(['status' => $fromTable->status]);

        // 2. Kosongkan meja asal[cite: 27]
        $fromTable->update(['status' => 'available']);

        // 3. Update transaksi agar terhubung ke meja yang baru[cite: 28]
        $transaction->update([
            'pool_table_id' => $toTable->id
        ]);

        return back()->with('success', "Berhasil pindah dari Meja {$fromTable->table_number} ke Meja {$toTable->table_number}");
    }

    return back()->with('error', 'Transaksi tidak ditemukan.');
}
public function stopBilling($id)
{
    // 1. Cari Meja berdasarkan ID
    $table = PoolTable::findOrFail($id);

    // 2. Cari Transaksi yang sedang berjalan di meja tersebut
    $transaction = Transaction::where('pool_table_id', $table->id)
                                ->where('status', 'running')
                                ->first();

    if ($transaction) {
        // 3. Update status transaksi menjadi finished
        $transaction->update([
            'status' => 'finished',
            'end_time' => now(), // Catat waktu selesai sebenarnya
        ]);

        // 4. Ubah status meja kembali ke available (Abu-abu)
        $table->update([
            'status' => 'available'
        ]);

        return redirect()->route('admin.dashboard')->with('success', "Meja {$table->table_number} telah selesai.");
    }

    return back()->with('error', 'Tidak ada transaksi aktif di meja ini.');
}
}
