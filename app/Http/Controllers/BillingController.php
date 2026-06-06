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
        $transaction = Transaction::where('pool_table_id', $id)
            ->where('status', 'running')->firstOrFail();

        $endTime = now();
        $startTime = Carbon::parse($transaction->start_time);
        $durationInMinutes = $startTime->diffInMinutes($endTime);

        if ($durationInMinutes < 1)
            $durationInMinutes = 1;

        $totalPrice = 0;
        $ruleId = null;

        if ($transaction->billing_type === 'package' && $transaction->package) {
            $totalPrice = $transaction->package->price;

        } elseif ($transaction->billing_type === 'personal') {
            // ✅ PERSONAL: Hitung per-segmen tarif dinamis
            $rule = $this->findMatchingPricingRuleAt($startTime); // untuk simpan rule_id
            $ruleId = $rule?->id;

            $calculated = $this->calculatePersonalBilling($transaction->start_time, $endTime);

            // Ambil min_charge dari rule saat open (atau rule sekarang sebagai fallback)
            $currentRule = $this->findMatchingPricingRuleAt($startTime);
            $minCharge = $currentRule?->min_charge ?? 10000;

            // ✅ Terapkan min_charge
            $totalPrice = max($calculated, $minCharge);

        } elseif ($transaction->billing_type === 'hourly') {
            // HOURLY: tetap pakai rule saat start
            $rule = $this->findMatchingPricingRuleAt($startTime);

            if ($rule) {
                $ruleId = $rule->id;
                $calculatedPrice = ($durationInMinutes / 60) * $rule->price_per_hour;
                $totalPrice = max($calculatedPrice, $rule->min_charge);
            } else {
                $totalPrice = ceil($durationInMinutes / 60) * 30000;
            }
        }

        $transaction->update([
            'end_time' => $endTime,
            'duration' => $durationInMinutes,
            'pricing_rule_id' => $transaction->pricing_rule_id ?? $ruleId,
            'total_price' => (int) $totalPrice,
            'status' => 'finished'
        ]);

        $table->update(['status' => 'available']);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Meja ' . $table->table_number . ' berhasil diselesaikan! Total: Rp ' . number_format($totalPrice, 0, ',', '.'));
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
            $minCharge = $rule ? $rule->min_charge : 10000;

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
            ->map(function ($order) {
                return [
                    'product_name' => $order->fnbProduct->name ?? 'Menu FnB',
                    'price' => (int) ($order->price ?? 0),
                    'qty' => (int) $order->qty,
                    'subtotal' => (int) $order->subtotal
                ];
            });

        return response()->json([
            'success' => true,
            'transaction_id' => $transaction->id,
            'customer_name' => $transaction->customer_name,
            'billing_price' => (int) $billingPrice,
            'fnb_orders' => $orders,
            'total_fnb' => (int) $orders->sum('subtotal')
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
                $end = $rule->end_time;

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


    private function calculatePersonalBilling($startTime, $endTime)
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $rules = \App\Models\PricingRule::all();
        $totalPrice = 0;

        // Iterasi menit per menit dari start sampai end
        // Untuk efisiensi, kita gunakan segmen — tidak benar-benar loop tiap menit
        // Strategi: kumpulkan semua breakpoint waktu lalu hitung tiap segmen

        // 1. Kumpulkan semua breakpoint (titik pergantian tarif) antara start dan end
        $breakpoints = [$start->copy()];

        // Cek setiap rule — apakah start_time-nya ada di antara start dan end?
        foreach ($rules as $rule) {
            $ruleStartH = (int) substr($rule->start_time, 0, 2);
            $ruleStartM = (int) substr($rule->start_time, 3, 2);

            // Cek tanggal start dan end (bisa beda hari jika melewati tengah malam)
            $daysDiff = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());

            for ($d = 0; $d <= $daysDiff; $d++) {
                $breakpointCandidate = $start->copy()->startOfDay()->addDays($d)
                    ->setHour($ruleStartH)->setMinute($ruleStartM)->setSecond(0);

                // Hanya tambahkan jika breakpoint ini berada DI ANTARA start dan end
                if ($breakpointCandidate->greaterThan($start) && $breakpointCandidate->lessThan($end)) {
                    $breakpoints[] = $breakpointCandidate->copy();
                }
            }
        }

        // Tambahkan end sebagai breakpoint terakhir
        $breakpoints[] = $end->copy();

        // 2. Urutkan breakpoints
        usort($breakpoints, fn($a, $b) => $a->timestamp - $b->timestamp);

        // 3. Hapus duplikat
        $unique = [];
        foreach ($breakpoints as $bp) {
            $key = $bp->format('Y-m-d H:i');
            if (!isset($unique[$key])) {
                $unique[$key] = $bp;
            }
        }
        $breakpoints = array_values($unique);

        // 4. Hitung harga tiap segmen
        for ($i = 0; $i < count($breakpoints) - 1; $i++) {
            $segStart = $breakpoints[$i];
            $segEnd = $breakpoints[$i + 1];
            $segMinutes = $segStart->diffInMinutes($segEnd);

            if ($segMinutes <= 0)
                continue;

            // Cari rule yang berlaku di titik tengah segmen ini
            $midPoint = $segStart->copy()->addSeconds($segStart->diffInSeconds($segEnd) / 2);
            $rule = $this->findMatchingPricingRuleAt($midPoint);

            if ($rule) {
                $pricePerMinute = $rule->price_per_hour / 60;
                $totalPrice += $pricePerMinute * $segMinutes;
            }
        }

        return round($totalPrice);
    }

    private function findMatchingPricingRuleAt(Carbon $time)
    {
        $timeString = $time->format('H:i:s');
        $dayOfWeek = $time->isoweekday();

        // Dini hari 00:00-06:59 masih ikut hari operasional kemarin
        if ($timeString >= '00:00:00' && $timeString < '07:00:00') {
            $dayOfWeek = $dayOfWeek == 1 ? 7 : $dayOfWeek - 1;
        }

        $rules = \App\Models\PricingRule::all();

        // Pass 1: cocok hari DAN jam
        foreach ($rules as $rule) {
            $activeDays = explode(',', str_replace(' ', '', $rule->active_days));
            if (!in_array((string) $dayOfWeek, $activeDays))
                continue;

            $start = $rule->start_time;
            $end = $rule->end_time;

            if ($start > $end) { // melewati tengah malam
                if ($timeString >= $start || $timeString <= $end)
                    return $rule;
            } else {
                if ($timeString >= $start && $timeString <= $end)
                    return $rule;
            }
        }

        // Pass 2: fallback ke rule yang harinya cocok
        foreach ($rules as $rule) {
            $activeDays = explode(',', str_replace(' ', '', $rule->active_days));
            if (in_array((string) $dayOfWeek, $activeDays))
                return $rule;
        }

        return \App\Models\PricingRule::first();
    }

}
