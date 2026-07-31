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
        $statusMeja = 'playing';

        $packageId = $request->package_id ?? null;

        if ($request->duration === 'manual') {
            $billingType = 'hourly';
            $duration = $request->manual_hours * 60;
            $endTime = $startTime->copy()->addMinutes($duration);
        } elseif ($request->duration === 'personal') {
            $billingType = 'personal';
            $statusMeja = 'personal';
            $endTime = null;
        } else {
            $billingType = 'package';
            $duration = (int) $request->duration;
            $endTime = $startTime->copy()->addMinutes($duration);

            // Fallback: Jika package_id tidak terkirim dari form, cari berdasarkan durasi
            if (!$packageId) {
                $matchedPackage = \App\Models\Package::where('duration_value', $duration)->first();
                $packageId = $matchedPackage ? $matchedPackage->id : null;
            }

            // --- VALIDASI BACKEND UNTUK PAKET PROMO ---
            if ($packageId) {
                $package = \App\Models\Package::find($packageId);

                if ($package) {
                    $now = \Carbon\Carbon::now();
                    $currentTime = $now->format('H:i:s');
                    $todayISO = (string) $now->dayOfWeekIso;

                    // Ambil aturan hari aktif dari pricing_rules
                    $weekdayRule = \App\Models\PricingRule::where('day_type', 'weekday')->first();
                    $weekendRule = \App\Models\PricingRule::where('day_type', 'weekend')->first();

                    $weekdayDays = $weekdayRule && $weekdayRule->active_days
                        ? explode(',', $weekdayRule->active_days)
                        : ['1', '2', '3', '4'];

                    $weekendDays = $weekendRule && $weekendRule->active_days
                        ? explode(',', $weekendRule->active_days)
                        : ['5', '6', '7'];

                    $isTodayWeekday = in_array($todayISO, $weekdayDays);
                    $isTodayWeekend = in_array($todayISO, $weekendDays);

                    $pkgDayType = strtolower($package->day_type);

                    // 1. Validasi Hari (Jika 'both', skip pengecekan hari karena berlaku setiap hari)
                    if ($pkgDayType === 'weekend' && !$isTodayWeekend) {
                        return back()->withErrors(['duration' => 'Paket promo ini hanya berlaku pada hari Weekend.']);
                    }

                    if ($pkgDayType === 'weekday' && !$isTodayWeekday) {
                        return back()->withErrors(['duration' => 'Paket promo me ini hanya berlaku pada hari Weekday.']);
                    }

                    // 2. Validasi Jam Aktif (active_from & active_to)
                    if ($package->active_from && $package->active_to) {
                        $pkgStart = \Carbon\Carbon::parse($package->active_from)->format('H:i:s');
                        $pkgEnd = \Carbon\Carbon::parse($package->active_to)->format('H:i:s');

                        $isTimeValid = false;
                        if ($pkgStart <= $pkgEnd) {
                            // Rentang jam normal (contoh: 11:30 - 22:00)
                            $isTimeValid = ($currentTime >= $pkgStart && $currentTime <= $pkgEnd);
                        } else {
                            // Rentang jam lewat tengah malam (contoh: 22:00 - 03:00)
                            $isTimeValid = ($currentTime >= $pkgStart || $currentTime <= $pkgEnd);
                        }

                        if (!$isTimeValid) {
                            return back()->withErrors(['duration' => 'Paket promo ini sedang tidak aktif pada jam sekarang.']);
                        }
                    }
                }
            }
            // --- AKHIR VALIDASI BACKEND ---
        }

        $table->update(['status' => $statusMeja]);

        // 1. Simpan Transaksi
        $transaction = Transaction::create([
            'user_id' => auth()->id() ?? 1,
            'pool_table_id' => $table->id,
            'customer_name' => $request->customer_name,
            'billing_type' => $billingType,
            'package_id' => $packageId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $duration,
            'status' => 'running',
        ]);

        // 2. OTOMATIS TAMBAHKAN FNB INCLUDE PAKET (HARGA = 0)
        if ($billingType === 'package' && $packageId) {
            $package = \App\Models\Package::with('fnbProducts')->find($packageId);

            if ($package && $package->fnbProducts->count() > 0) {
                foreach ($package->fnbProducts as $fnb) {
                    $includeQty = $fnb->pivot->stock ?? 1;

                    \App\Models\OrderFnb::create([
                        'transaction_id' => $transaction->id,
                        'fnb_product_id' => $fnb->id,
                        'customer_name' => $transaction->customer_name,
                        'stock' => $includeQty,
                        'price' => 0, // Rp 0 bawaan paket
                        'subtotal' => 0,
                        'payment_status' => 'unpaid',
                    ]);

                    // Kurangi stok
                    $fnb->decrement('stock', $includeQty);
                }
            }
        }

        return back()->with('success', 'Meja ' . $table->table_number . ' dimulai!');
    }

    public function moveTable(Request $request)
    {
        $fromTable = PoolTable::findOrFail($request->from_table_id);
        $toTable = PoolTable::findOrFail($request->to_table_id);

        $transaction = Transaction::where('pool_table_id', $fromTable->id)
            ->where('status', 'running')
            ->first();

        if ($transaction) {
            $toTable->update(['status' => $fromTable->status]);
            $fromTable->update(['status' => 'available']);
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
            $rule = $this->findMatchingPricingRuleAt($startTime);
            $ruleId = $rule?->id;

            $calculated = $this->calculatePersonalBilling($transaction->start_time, $endTime);
            $currentRule = $this->findMatchingPricingRuleAt($startTime);
            $minCharge = $currentRule?->min_charge ?? 10000;

            $totalPrice = max($calculated, $minCharge);
        } elseif ($transaction->billing_type === 'hourly') {
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
        $packageId = $request->package_id ?? null;

        if ($request->duration === 'personal') {
            $billingType = 'personal';
            $statusMeja = 'personal';
        } elseif ($request->duration === 'manual') {
            $billingType = 'hourly';
            $duration = $request->manual_hours * 60;
            $endTime = $startTime->copy()->addMinutes($duration);
        } else {
            $billingType = 'package';
            $duration = (int) $request->duration;
            $endTime = $startTime->copy()->addMinutes($duration);

            if (!$packageId) {
                $matchedPackage = \App\Models\Package::where('duration_value', $duration)->first();
                $packageId = $matchedPackage ? $matchedPackage->id : null;
            }
        }

        $tables = PoolTable::whereBetween('table_number', [$request->start_table, $request->end_table])
            ->where('status', 'available')
            ->get();

        if ($tables->isEmpty()) {
            return back()->with('error', 'Gagal menembak billing massal! Tidak ada meja kosong di rentang tersebut.');
        }

        foreach ($tables as $table) {
            $table->update(['status' => $statusMeja]);

            $transaction = Transaction::create([
                'user_id' => auth()->id() ?? 1,
                'pool_table_id' => $table->id,
                'customer_name' => strtoupper($request->customer_name) . " (M-{$table->table_number})",
                'billing_type' => $billingType,
                'package_id' => $packageId,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => $duration,
                'status' => 'running',
            ]);

            if ($billingType === 'package' && $packageId) {
                $package = \App\Models\Package::with('fnbProducts')->find($packageId);
                if ($package && $package->fnbProducts->count() > 0) {
                    foreach ($package->fnbProducts as $fnb) {
                        $includeQty = $fnb->pivot->stock ?? 1;

                        \App\Models\OrderFnb::create([
                            'transaction_id' => $transaction->id,
                            'fnb_product_id' => $fnb->id,
                            'customer_name' => $transaction->customer_name,
                            'stock' => $includeQty,
                            'price' => 0,
                            'subtotal' => 0,
                            'payment_status' => 'unpaid',
                        ]);

                        $fnb->decrement('stock', $includeQty);
                    }
                }
            }
        }

        return back()->with('success', 'BOOM! Billing Massal Roket dimulai untuk ' . $tables->count() . ' meja sekaligus!');
    }

    public function getActiveDetail($tableId)
    {
        try {
            $table = \App\Models\PoolTable::findOrFail($tableId);
            $activeTx = $table->transactions()
                ->where('status', 'running')
                ->with(['package', 'orderFnbs.fnbProduct'])
                ->first();

            if (!$activeTx) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada transaksi aktif.'
                ]);
            }

            // 1. HITUNG BIAYA BILLING MEJA (PACKAGE / HOURLY / PERSONAL)
            $billingPrice = 0;

            if ($activeTx->billing_type === 'package' || (!empty($activeTx->package_id) && $activeTx->package)) {
                // Jika Paket Promo
                $billingPrice = (float) ($activeTx->package->price ?? $activeTx->total_price ?? $activeTx->package_price ?? 0);

            } elseif ($activeTx->billing_type === 'hourly' || $activeTx->billing_type === 'personal') {
                // Cek aturan harga saat ini
                $rule = $this->findCurrentPricingRule();
                $pricePerHour = $rule ? $rule->price_per_hour : 29000;
                $minCharge = $rule ? $rule->min_charge : 10000;

                if ($activeTx->billing_type === 'hourly') {
                    $durationMinutes = $activeTx->duration ?? 60;
                    $calculated = ($durationMinutes / 60) * $pricePerHour;
                } else {
                    // personal = elapsed real-time
                    $startTime = \Carbon\Carbon::parse($activeTx->start_time);
                    $elapsedMinutes = $startTime->diffInMinutes(now());
                    $calculated = ($elapsedMinutes / 60) * $pricePerHour;
                }

                $billingPrice = max($calculated, $minCharge);
            } else {
                // Fallback jika ada nominal tersimpan langsung
                $billingPrice = (float) ($activeTx->total_price ?? 0);
            }

            // 2. LOAD PESANAN FNB UNPAID (termasuk pengecekan item bawaan paket)
            $fnbOrdersList = [];
            $fnbOrders = $activeTx->orderFnbs()
                ->where('payment_status', 'unpaid')
                ->get();

            foreach ($fnbOrders as $order) {
                $isPkg = ((float) $order->price === 0.0 || $order->is_package_include);

                $fnbOrdersList[] = [
                    'id' => $order->fnb_product_id,
                    'product_name' => $order->fnbProduct->name ?? 'Produk FnB',
                    'price' => (int) $order->price,
                    'stock' => (int) $order->stock,
                    'subtotal' => (int) $order->subtotal,
                    'is_package_item' => $isPkg
                ];
            }

            $totalFnb = array_sum(array_column($fnbOrdersList, 'subtotal'));

            // 3. RETURN RESPONSE JSON
            return response()->json([
                'success' => true,
                'transaction_id' => $activeTx->id,
                'customer_name' => $activeTx->customer_name ?? 'GUEST',
                'billing_price' => (int) round($billingPrice),
                'fnb_orders' => $fnbOrdersList,
                'total_fnb' => (int) $totalFnb,
                'grand_total' => (int) round($billingPrice) + (int) $totalFnb
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error Server: ' . $e->getMessage()
            ], 500);
        }
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

        if ($currentTimeString >= '00:00:00' && $currentTimeString <= '03:00:00') {
            $currentDayOfWeek = $currentDayOfWeek == 1 ? 7 : $currentDayOfWeek - 1;
        }

        $rules = \App\Models\PricingRule::all();

        foreach ($rules as $rule) {
            $activeDays = explode(',', str_replace(' ', '', $rule->active_days));

            if (in_array($currentDayOfWeek, $activeDays)) {
                $start = $rule->start_time;
                $end = $rule->end_time;

                if ($start > $end) {
                    if ($currentTimeString >= $start || $currentTimeString <= $end) {
                        return $rule;
                    }
                } else {
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

        $breakpoints = [$start->copy()];

        foreach ($rules as $rule) {
            $ruleStartH = (int) substr($rule->start_time, 0, 2);
            $ruleStartM = (int) substr($rule->start_time, 3, 2);

            $daysDiff = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay());

            for ($d = 0; $d <= $daysDiff; $d++) {
                $breakpointCandidate = $start->copy()->startOfDay()->addDays($d)
                    ->setHour($ruleStartH)->setMinute($ruleStartM)->setSecond(0);

                if ($breakpointCandidate->greaterThan($start) && $breakpointCandidate->lessThan($end)) {
                    $breakpoints[] = $breakpointCandidate->copy();
                }
            }
        }

        $breakpoints[] = $end->copy();

        usort($breakpoints, fn($a, $b) => $a->timestamp - $b->timestamp);

        $unique = [];
        foreach ($breakpoints as $bp) {
            $key = $bp->format('Y-m-d H:i');
            if (!isset($unique[$key])) {
                $unique[$key] = $bp;
            }
        }
        $breakpoints = array_values($unique);

        for ($i = 0; $i < count($breakpoints) - 1; $i++) {
            $segStart = $breakpoints[$i];
            $segEnd = $breakpoints[$i + 1];
            $segMinutes = $segStart->diffInMinutes($segEnd);

            if ($segMinutes <= 0)
                continue;

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

        if ($timeString >= '00:00:00' && $timeString < '07:00:00') {
            $dayOfWeek = $dayOfWeek == 1 ? 7 : $dayOfWeek - 1;
        }

        $rules = \App\Models\PricingRule::all();

        foreach ($rules as $rule) {
            $activeDays = explode(',', str_replace(' ', '', $rule->active_days));
            if (!in_array((string) $dayOfWeek, $activeDays))
                continue;

            $start = $rule->start_time;
            $end = $rule->end_time;

            if ($start > $end) {
                if ($timeString >= $start || $timeString <= $end)
                    return $rule;
            } else {
                if ($timeString >= $start && $timeString <= $end)
                    return $rule;
            }
        }

        foreach ($rules as $rule) {
            $activeDays = explode(',', str_replace(' ', '', $rule->active_days));
            if (in_array((string) $dayOfWeek, $activeDays))
                return $rule;
        }

        return \App\Models\PricingRule::first();
    }
}
