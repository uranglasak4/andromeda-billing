<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaitingList;
use App\Models\PoolTable;
use App\Models\PricingRule;
use App\Models\Package;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
{
    // 1. Ambil 14 meja, urutkan berdasarkan nomor meja BESERTA TRANSAKSI AKTIFNYA (running)
    // Ini sangat krusial agar nama customer dan FnB orderan bisa terbaca di modal dashboard!
    $tables = PoolTable::with(['transactions' => function($query) {
        $query->where('status', 'running');
    }])->orderBy('table_number', 'asc')->get();

    $currentWaitingCount = WaitingList::where('status', 'waiting')->count();
    $pricingRules = PricingRule::all();
    $packages = Package::where('is_active', true)->get();

    $waitingCustomers = WaitingList::where('status', 'waiting')
                    ->whereDate('created_at', Carbon::today())
                    ->orderBy('created_at', 'asc')
                    ->get();

    // Re-assign count agar akurat sesuai data hari ini
    $currentWaitingCount = $waitingCustomers->count();

    // 2. --- LOGIKA DETEKSI HARGA OPERASIONAL (UNTUK DISPLAY INFO KASIR) ---
    $now = now();
    $currentTimeString = $now->format('H:i:s');
    $currentDayOfWeek = $now->isoweekday(); // 1 (Senin) s/d 7 (Minggu)

    // Skenario Dini Hari: Jam 00:00:00 s/d 03:00:00 masih ikut hari operasional kemarin
    if ($currentTimeString >= '00:00:00' && $currentTimeString <= '03:00:00') {
        $currentDayOfWeek = $currentDayOfWeek == 1 ? 7 : $currentDayOfWeek - 1;
    }

    $currentRule = null;
    foreach ($pricingRules as $rule) {
        $activeDays = explode(',', str_replace(' ', '', $rule->active_days));

        if (in_array($currentDayOfWeek, $activeDays)) {
            $start = $rule->start_time;
            $end = $rule->end_time;

            if ($start > $end) { // Aturan Lewat Tengah Malam
                if ($currentTimeString >= $start || $currentTimeString <= $end) {
                    $currentRule = $rule;
                    break;
                }
            } else { // Aturan Normal
                if ($currentTimeString >= $start && $currentTimeString <= $end) {
                    $currentRule = $rule;
                    break;
                }
            }
        }
    }

    // Fallback aman jika rule tidak terdeteksi
    if (!$currentRule) {
        $currentRule = $pricingRules->where('day_type', 'weekday')->first();
    }

    // Kirimkan semua data ke view secara utuh dan aman
    return view('admin.dashboardadmin', compact('tables', 'currentWaitingCount', 'waitingCustomers', 'pricingRules', 'packages', 'currentRule'));
}

public function getTablesStatus() {
    $now = now();

    // 1. Ambil semua data 14 meja biliar beserta transaksi aktifnya
    $tables = PoolTable::with(['transactions' => function($query) {
        $query->where('status', 'running');
    }])->orderBy('table_number', 'asc')->get();

    // Koreksi status otomatis di latar belakang saat dashboard/monitor melakukan polling
    foreach ($tables as $table) {
        if ($table->status === 'playing') {
            $activeTx = $table->transactions->first();
            if ($activeTx && $activeTx->end_time) {
                if ($now->greaterThanOrEqualTo(\Carbon\Carbon::parse($activeTx->end_time))) {
                    $table->status = 'timeout';
                    $table->save();
                }
            }
        }
    }

    // 2. AMBIL DATA WAITING LIST YANG AKTIF (Statusnya 'waiting')
$waitingList = WaitingList::whereDate('created_at', Carbon::today())
    ->whereIn('status', ['waiting', 'not_verified', 'verified', 'call']) // <-- Sekarang ikut ditarik!
    ->orderBy('created_at', 'asc')
    ->get();

    // 3. BUNGKUS KEDUANYA MENJADI SATU KESATUAN JSON RESPONSE
    return response()->json([
        'tables' => $tables,
        'waiting_list' => $waitingList
    ]);
}
}
