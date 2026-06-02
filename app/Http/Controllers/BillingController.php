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
    $table = PoolTable::findOrFail($id);
    $transaction = Transaction::where('pool_table_id', $table->id)
                                ->whereIn('status', ['running', 'active'])
                                ->first();

    if ($transaction) {
        $startTime = \Carbon\Carbon::parse($transaction->start_time);
        $endTime = now();

        $duration = $startTime->diffInMinutes($endTime);
        if($duration <= 0) $duration = 1;

        $totalPrice = 0;

        // --- MULAI LOGIKA BARU ---
        if ($transaction->billing_type == 'package') {
            $totalPrice = $transaction->package->price ?? 0;
        } else {
            // 1. Ambil info hari dan jam sekarang
            $now = now();
            $dayOfWeek = $now->dayOfWeekIso; // 1 (Senin) - 7 (Minggu)

            // 2. Cari rule yang sesuai dengan hari dan rentang jam saat ini
            // Pastikan di database, active_days berisi string seperti "1,2,3,4"
            $rule = \App\Models\PricingRule::where('active_days', 'like', "%$dayOfWeek%")
                ->whereTime('start_time', '<=', $now->format('H:i:s'))
                ->whereTime('end_time', '>=', $now->format('H:i:s'))
                ->first();

            // Gunakan harga dari database, jika tidak ketemu pakai default (27k)
            $pricePerHour = $rule ? $rule->price_per_hour : 27000;
            $minCharge = $rule ? $rule->min_charge : 10000;

            $calculatedPrice = ($duration / 60) * $pricePerHour;

            // 3. Terapkan Aturan Minimum Charge (Andromeda Rule)
            $totalPrice = ($calculatedPrice < $minCharge) ? $minCharge : $calculatedPrice;
        }
// --- INTEGRASI FNB TIPE 1 ---
    // Hitung semua item FnB milik transaksi ini yang statusnya masih 'unpaid'
    $totalFnb = $transaction->orderFnbs()->where('payment_status', 'unpaid')->sum('subtotal');
    $grandTotal = $totalPrice + $totalFnb;

    // Set status order FnB di meja ini menjadi paid (Lunas) karena dibayar saat checkout meja
    $transaction->orderFnbs()->where('payment_status', 'unpaid')->update(['payment_status' => 'paid']);

        $transaction->update([
            'status' => 'finished',
            'end_time' => $endTime,
            'duration' => $duration,
            'total_price' => $grandTotal,
        ]);

$table->update(['status' => 'available']);

    return redirect()->route('admin.dashboard')->with('success', "Meja {$table->table_number} Selesai. Total Bayar (Meja + FnB): Rp " . number_format($grandTotal, 0, ',', '.'));
    }

    return back()->with('error', 'Transaksi tidak ditemukan.');
}

public function massOpenTable(Request $request)
{
    $request->validate([
        'start_table'   => 'required|integer',
        'end_table'     => 'required|integer',
        'customer_name' => 'required|string|max:30',
        'duration'      => 'required'
    ]);

    $startTime = now();
    $endTime = null;
    $duration = null;
    $billingType = 'personal';
    $statusMeja = 'playing';

    // 1. Tentukan Durasi Paket berdasarkan pilihan
    if ($request->duration === 'personal') {
        $billingType = 'personal';
        $statusMeja = 'personal';
    } else {
        $billingType = 'package';
        $duration = (int) $request->duration;
        $endTime = $startTime->copy()->addMinutes($duration);
    }

    // 2. Ambil semua meja yang berada di rentang nomor yang diinput (Hanya yang statusnya AVAILABLE)
    $tables = PoolTable::whereBetween('table_number', [$request->start_table, $request->end_table])
                       ->where('status', 'available')
                       ->get();

    if ($tables->isEmpty()) {
        return back()->with('error', 'Gagal menembak billing massal! Tidak ada meja kosong (Available) di rentang nomor tersebut.');
    }

    // 3. Loop untuk menembak data transaksi sekaligus
    foreach ($tables as $table) {
        $table->update(['status' => $statusMeja]);

        Transaction::create([
            'user_id'       => auth()->id() ?? 1,
            'pool_table_id' => $table->id,
            'customer_name' => strtoupper($request->customer_name) . " (M-{$table->table_number})",
            'billing_type'  => $billingType,
            'start_time'    => $startTime,
            'end_time'      => $endTime,
            'duration'      => $duration,
            'status'        => 'running',
        ]);
    }

    return back()->with('success', 'BOOM! Paket Massal Roket berhasil diaktifkan untuk ' . $tables->count() . ' meja sekaligus!');
}

public function getActiveDetail($table_id)
{
    // 1. Cari transaksi running di meja tersebut menggunakan pool_table_id
    $transaction = \App\Models\Transaction::where('pool_table_id', $table_id)
                    ->where('status', 'running')
                    ->first();

    if (!$transaction) {
        return response()->json([
            'success' => false,
            'message' => 'Tidak ada transaksi aktif'
        ]);
    }

    // 2. HITUNG HARGA BILLING SECARA REAL-TIME JIKA TRANSAKSI MASIH RUNNING
    $billingPrice = 0;
    $now = now();
    $startTime = \Carbon\Carbon::parse($transaction->start_time);

    if ($transaction->billing_type === 'hourly') {
        $elapsedMinutes = $startTime->diffInMinutes($now);

        // Mengambil rule pricing pertama, jika tidak ada gunakan default Rp 27.000 / jam
        $pricing = \App\Models\PricingRule::first();
        $pricePerHour = $pricing ? $pricing->price_per_hour : 27000;
        $pricePerMinute = $pricePerHour / 60;

        $billingPrice = round($elapsedMinutes * $pricePerMinute);

        // Terapkan minimum charge jika ada
        $minCharge = $pricing ? $pricing->min_charge : 10000;
        if ($billingPrice < $minCharge) {
            $billingPrice = $minCharge;
        }
    } elseif ($transaction->billing_type === 'package') {
        // Jika paket, ambil harga flat dari relasi package atau total_price sementara
        $billingPrice = $transaction->total_price ?? 0;
    } else {
        // Tipe personal / open time berjalan
        $billingPrice = $transaction->total_price ?? 0;
    }

    // 3. Ambil order FnB yang belum lunas (unpaid) milik transaksi ini
    $orders = $transaction->orderFnbs()
            ->with('fnbProduct')
            ->where('payment_status', 'unpaid')
            ->get()
            ->map(function($order) {
                return [
                    'product_name' => $order->fnbProduct->name ?? 'Menu FnB',
                    'price'        => (int) ($order->price ?? 0), // Perbaikan: Hapus anisotropy_cast yang bikin crash
                    'qty'          => (int) $order->qty,
                    'subtotal'     => (int) $order->subtotal
                ];
            });

    return response()->json([
        'success'       => true,
        'billing_price' => (int) $billingPrice,
        'fnb_orders'    => $orders,
        'total_fnb'     => (int) $orders->sum('subtotal')
    ]);
}

}
