<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WaitingList;
use App\Models\PoolTable;
use App\Models\PricingRule;
use App\Models\Package;
use App\Models\Transaction;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        // 1. Ambil meja beserta transaksi aktifnya
        $tables = PoolTable::with([
            'transactions' => function ($query) {
                $query->where('status', 'running');
            }
        ])->orderBy('table_number', 'asc')->get();

        $pricingRules = PricingRule::all();
        $packages = Package::where('is_active', true)->get();

        // 🟢 TAMBAHAN TAHAP 2: Ambil WL aktif (Status call, verified, waiting) untuk Dropdown Billing
        $waitingCustomers = WaitingList::whereDate('created_at', Carbon::today())
            ->whereIn('status', ['call', 'verified', 'waiting'])
            ->orderByRaw("
            CASE
                WHEN status = 'call' THEN 1
                WHEN tipe = 'onsite' OR status = 'verified' THEN 2
                WHEN status = 'not_verified' THEN 3
                ELSE 4
            END ASC
        ")
            ->orderBy('created_at', 'asc')
            ->get();

        $currentWaitingCount = $waitingCustomers->count();

        // Data transaksi unpaid
        $unpaidTransactions = Transaction::where('status', 'unpaid')
            ->with('poolTable')
            ->latest()
            ->get();

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

        // Kirimkan semua data ke view secara utuh dan aman (Ditambahkan 'unpaidTransactions')
        return view('admin.dashboardadmin', compact('tables', 'currentWaitingCount', 'waitingCustomers', 'pricingRules', 'packages', 'currentRule', 'unpaidTransactions'));
    }

    public function getTablesStatus()
    {
        $now = now();

        // 1. Ambil semua data 16 meja biliar beserta transaksi aktifnya
        $tables = PoolTable::with([
            'transactions' => function ($query) {
                $query->where('status', 'running');
            }
        ])->orderBy('table_number', 'asc')->get();

        // Koreksi status otomatis jika waktu sewa meja sudah habis
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

        // 2A. QUERY UNTUK ADMIN KASIR (HIERARKI PRESISI 3 TINGKAT)
        // Prioritas 1: Online Verified (status = 'verified')
        // Prioritas 2: On-Site Kasir (tipe = 'onsite')
        // Prioritas 3: Online Unverified (status = 'not_verified')
        $waitingListAdmin = WaitingList::whereDate('created_at', Carbon::today())
            ->whereIn('status', ['waiting', 'not_verified', 'verified', 'call'])
            ->orderByRaw("
            CASE
                WHEN status = 'verified' THEN 1
                WHEN tipe = 'onsite' THEN 2
                WHEN status = 'not_verified' THEN 3
                ELSE 4
            END ASC
        ")
            ->orderBy('created_at', 'asc') // Urut kronologis jam daftar dalam kelompok yang sama
            ->get();

        // 2B. QUERY UNTUK LIVE MONITOR (MURNI KRONOLOGIS JAM DAFTAR)
        $waitingListMonitor = WaitingList::whereDate('created_at', Carbon::today())
            ->whereIn('status', ['waiting', 'not_verified', 'verified', 'call'])
            ->orderBy('created_at', 'asc')
            ->get();

        // 3. Response JSON
        return response()->json([
            'tables' => $tables,
            'waiting_list' => $waitingListAdmin,          // Dipakai Admin Kasir
            'waiting_list_monitor' => $waitingListMonitor // Dipakai Live Monitor Board
        ]);
    }
}
