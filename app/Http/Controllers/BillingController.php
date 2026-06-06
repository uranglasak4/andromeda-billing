<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PoolTable;
use App\Models\Transaction;
use App\Models\Package;
use Carbon\Carbon;

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
        $transaction = Transaction::where('pool_table_id', $id)->where('status', 'running')->firstOrFail();

        $endTime = now();
        $startTime = Carbon::parse($transaction->start_time);
        $durationInMinutes = $startTime->diffInMinutes($endTime);

        // Proteksi durasi minimal 1 menit
        if ($durationInMinutes < 1) {
            $durationInMinutes = 1;
        }

        $totalPrice = 0;
        $ruleId = null;

        if ($transaction->billing_type === 'package' && $transaction->package) {
            // Jika pakai paket, harga flat dari master paket
            $totalPrice = $transaction->package->price;
        } else {
            // JIKA HOURLY ATAU PERSONAL -> Cari aturan tarif dinamis berdasarkan waktu start!
            $rule = $this->findMatchingPricingRule($transaction->start_time);

            if ($rule) {
                $ruleId = $rule->id;
                // Hitung harga real berdasarkan menit (Durasi / 60 menit * Harga per jam)
                $calculatedPrice = ($durationInMinutes / 60) * $rule->price_per_hour;

                // Terapkan batas Minimum Charge dari master rule
                $totalPrice = max($calculatedPrice, $rule->min_charge);

                // Opsional: Jika ingin dibulatkan ke kelipatan Rp 100 atau Rp 500 terdekat, aktifkan ini:
                // $totalPrice = ceil($totalPrice / 500) * 500;
            } else {
                $totalPrice = ceil($durationInMinutes / 60) * 30000; // Backup jika master kosong
            }
        }

        $transaction->update([
            'end_time' => $endTime,
            'duration' => $durationInMinutes,
            'pricing_rule_id' => $transaction->pricing_rule_id ?? $ruleId, // Simpan rule id yang digunakan
            'total_price' => (int) $totalPrice,
            'status' => 'finished'
        ]);

        $table->update(['status' => 'available']);

        return redirect()->route('admin.dashboard')->with('success', 'Meja ' . $table->table_number . ' berhasil diselesaikan! Total: Rp ' . number_format($totalPrice, 0, ',', '.'));
    }


    public function massOpenTable(Request $request)
    {
        $request->validate([
            'start_table' => 'required|integer',
            'end_table' => 'required|integer',
            'customer_name' => 'required|string|max:30',
            'duration' => 'required'
        ]);

        $startTime = now();
        $endTime = null;
        $duration = null;
        $billingType = 'personal';
        $statusMeja = 'playing';

        // Perbaikan Integrasi Kondisi Durasi dari Modal Rocket Baru
        if ($request->duration === 'personal') {
            $billingType = 'personal';
            $statusMeja = 'personal';
        } elseif ($request->duration === 'manual') {
            $billingType = 'hourly';
            $duration = $request->manual_hours * 60; // Konversi jam input ke menit
            $endTime = $startTime->copy()->addMinutes($duration);
        } else {
            $billingType = 'package';
            $duration = (int) $request->duration;
            $endTime = $startTime->copy()->addMinutes($duration);
        }

        // Ambil semua meja yang berada di rentang nomor yang diinput (Hanya yang statusnya AVAILABLE)
        $tables = PoolTable::whereBetween('table_number', [$request->start_table, $request->end_table])
            ->where('status', 'available')
            ->get();

        if ($tables->isEmpty()) {
            return back()->with('error', 'Gagal menembak billing massal! Tidak ada meja kosong (Available) di rentang nomor tersebut.');
        }

        // Loop transaksi massal
        foreach ($tables as $table) {
            $table->update(['status' => $statusMeja]);

            Transaction::create([
                'user_id' => auth()->id() ?? 1,
                'pool_table_id' => $table->id,
                'customer_name' => strtoupper($request->customer_name) . " (M-{$table->table_number})",
                'billing_type' => $billingType,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $duration,
                'status' => 'running',
            ]);
        }

        return back()->with('success', 'BOOM! Billing Massal Roket dimulai untuk ' . $tables->count() . ' meja sekaligus!');
    }

    public function getActiveDetail($table_id)
{
    $transaction = \App\Models\Transaction::where('pool_table_id', $table_id)
                    ->where('status', 'running')
                    ->first();

    if (!$transaction) {
        return response()->json(['success' => false, 'message' => 'Tidak ada transaksi aktif']);
    }

    $billingPrice = 0;
    $now = now();
    $startTime = \Carbon\Carbon::parse($transaction->start_time);

    if ($transaction->billing_type === 'hourly' || $transaction->billing_type === 'personal') {

    // ✅ Pakai rule SEKARANG, bukan rule saat meja dibuka
    $rule = $this->findCurrentPricingRule();

    $pricePerHour = $rule ? $rule->price_per_hour : 29000;
    $minCharge    = $rule ? $rule->min_charge : 10000;

    if ($transaction->billing_type === 'hourly') {
        $durationMinutes = $transaction->duration ?? 60;
        $calculated = ($durationMinutes / 60) * $pricePerHour;
    } else {
        // personal = elapsed real-time
        $elapsedMinutes = Carbon::parse($transaction->start_time)->diffInMinutes(now());
        $calculated = ($elapsedMinutes / 60) * $pricePerHour;
    }

    $billingPrice = max($calculated, $minCharge);

} elseif ($transaction->billing_type === 'package') {
    $billingPrice = $transaction->total_price ?? 0;
}

    $orders = $transaction->orderFnbs()
            ->with('fnbProduct')
            ->where('payment_status', 'unpaid')
            ->get()
            ->map(function($order) {
                return [
                    'product_name' => $order->fnbProduct->name ?? 'Menu FnB',
                    'price'        => (int) ($order->price ?? 0),
                    'qty'          => (int) $order->qty,
                    'subtotal'     => (int) $order->subtotal
                ];
            });

    return response()->json([
        'success'        => true,
        'transaction_id' => $transaction->id,
        'customer_name'  => $transaction->customer_name,
        'billing_price'  => (int) $billingPrice,
        'fnb_orders'     => $orders,
        'total_fnb'      => (int) $orders->sum('subtotal')
    ]);
}

    public function updateCustomerName(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:transactions,id',
            'customer_name' => 'required|string|max:50'
        ]);

        try {
            $transaction = Transaction::findOrFail($request->transaction_id);
            $transaction->update([
                'customer_name' => strtoupper($request->customer_name)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Nama customer berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengubah nama: ' . $e->getMessage()
            ], 500);
        }
    }

private function findCurrentPricingRule()
{
    $now = Carbon::now();
    $currentTimeString = $now->format('H:i:s');
    $currentDayOfWeek = $now->isoweekday();

    // Skenario Dini Hari: 00:00 - 03:00 masih ikut hari operasional kemarin
    if ($currentTimeString >= '00:00:00' && $currentTimeString <= '03:00:00') {
        $currentDayOfWeek = $currentDayOfWeek == 1 ? 7 : $currentDayOfWeek - 1;
    }

    $rules = \App\Models\PricingRule::all();

    foreach ($rules as $rule) {
        $activeDays = explode(',', str_replace(' ', '', $rule->active_days));

        if (in_array($currentDayOfWeek, $activeDays)) {
            $start = $rule->start_time;
            $end   = $rule->end_time;

            if ($start > $end) { // Melewati tengah malam
                if ($currentTimeString >= $start || $currentTimeString <= $end) {
                    return $rule;
                }
            } else { // Normal
                if ($currentTimeString >= $start && $currentTimeString <= $end) {
                    return $rule;
                }
            }
        }
    }

    return \App\Models\PricingRule::first();
}

    private function findMatchingPricingRule($startTime)
    {
        $timeToCheck = Carbon::parse($startTime);
        $currentTimeString = $timeToCheck->format('H:i:s');
        $currentDayOfWeek = $timeToCheck->isoweekday();

        // Skenario Dini Hari: Jam 00:00:00 s/d 03:00:00 masih ikut hari operasional kemarin
        if ($currentTimeString >= '00:00:00' && $currentTimeString <= '03:00:00') {
            $currentDayOfWeek = $currentDayOfWeek == 1 ? 7 : $currentDayOfWeek - 1;
        }

        $rules = \App\Models\PricingRule::all();

        foreach ($rules as $rule) {
            $activeDays = explode(',', str_replace(' ', '', $rule->active_days));

            if (in_array($currentDayOfWeek, $activeDays)) {
                $start = $rule->start_time;
                $end = $rule->end_time;

                // Aturan Melewati Tengah Malam
                if ($start > $end) {
                    if ($currentTimeString >= $start || $currentTimeString <= $end) {
                        return $rule;
                    }
                }
                // Aturan Waktu Normal
                else {
                    if ($currentTimeString >= $start && $currentTimeString <= $end) {
                        return $rule;
                    }
                }
            }
        }

        return \App\Models\PricingRule::first();
    }

}
