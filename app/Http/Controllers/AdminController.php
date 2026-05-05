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
}