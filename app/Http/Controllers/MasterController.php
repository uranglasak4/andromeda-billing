<?php

namespace App\Http\Controllers;

use App\Models\PoolTable;
use App\Models\Transaction; // <--- Ganti dari Billing ke Transaction
use Illuminate\Http\Request;

class MasterController extends Controller
{
public function index()
{
    $tables = PoolTable::all();

    // Hitung total omzet yang sudah 'finished' hari ini
    $omzetHariIni = Transaction::where('status', 'finished')
                    ->whereDate('end_time', today())
                    ->sum('total_price');

    foreach ($tables as $table) {
        $historyToday = $table->transactions()
            ->where('status', 'finished')
            ->whereDate('end_time', today());

        $table->total_transaksi = $historyToday->count();
        $table->total_pendapatan = $historyToday->sum('total_price');
        $table->total_waktu = $historyToday->sum('duration');
    }

    $mejaTerisi = $tables->whereIn('status', ['playing', 'personal'])->count();
    $waitingLists = \App\Models\WaitingList::where('status', 'waiting')->get();

    return view('master.dashboardmaster', compact('tables', 'omzetHariIni', 'mejaTerisi', 'waitingLists'));
}
}
