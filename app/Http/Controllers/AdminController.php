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
        // Ambil 14 meja, urutkan berdasarkan nomor meja
        $tables = PoolTable::with(['transactions' => function($query) {
        $query->where('status', 'running');
    }])->orderBy('table_number', 'asc')->get();

        $currentWaitingCount = WaitingList::where('status', 'waiting')->count();

        $pricingRules = PricingRule::all();
    $packages = Package::where('is_active', true)->get();

        $waitingCustomers = WaitingList::where('status', 'waiting')
                        ->whereDate('created_at', Carbon::today())
                        ->orderBy('created_at', 'asc') // Pertama daftar di atas
                        ->get();

        $currentWaitingCount = $waitingCustomers->count();

    return view('admin.dashboardadmin', compact('tables', 'currentWaitingCount', 'waitingCustomers', 'pricingRules', 'packages'));
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
    $waitingList = WaitingList::where('status', 'waiting')
                                ->orderBy('created_at', 'asc')
                                ->get();

    // 3. BUNGKUS KEDUANYA MENJADI SATU KESATUAN JSON RESPONSE
    return response()->json([
        'tables' => $tables,
        'waiting_list' => $waitingList
    ]);
}
}
