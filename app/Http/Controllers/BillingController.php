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
        // --- SELESAI LOGIKA BARU ---

        $transaction->update([
            'status' => 'finished',
            'end_time' => $endTime,
            'duration' => $duration,
            'total_price' => $totalPrice,
        ]);

        $table->update(['status' => 'available']);

        return redirect()->route('admin.dashboard')->with('success', "Meja {$table->table_number} Selesai. Total: Rp " . number_format($totalPrice, 0, ',', '.'));
    }

    return back()->with('error', 'Transaksi tidak ditemukan.');
}


}
