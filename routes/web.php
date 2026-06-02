<?php


use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;

use App\Http\Controllers\WaitingListController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderFnbController;
use App\Models\PoolTable;
use App\Models\Transaction;
use App\Models\OrderFnb;
use Illuminate\Support\Facades\Auth;

// =========================================================================
// 1. HALAMAN DEPAN (Customer Live Monitor)
// =========================================================================
Route::get('/', function () {
    if (Auth::check()) {
        if (Auth::user()->role === 'master') {
            return redirect()->route('master.dashboard');
        } elseif (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }
    }
    return view('customer.index');
})->name('customer.index');

// Rute Pendaftaran Waiting List Mandiri untuk Customer (Tanpa Login)
Route::post('/customer/waiting-list/store', [WaitingListController::class, 'store'])->name('customer.waiting-list.store');

// Rute API Status Meja Publik (DIKELUARKAN dari middleware auth agar grid meja muncul)
Route::get('/api/tables/status', [AdminController::class, 'getTablesStatus'])->name('api.tables.status');


// 2. Route Login (Hanya bisa diakses jika BELUM login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// 3. Logout
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');


// =========================================================================
// 4. GROUP ROUTE ADMIN (Dikunci Ketat Khusus Role 'admin')
// =========================================================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');

    // TAMBAHKAN RUTE GET INI SUPAYA TIDAK ERROR NOT DEFINED, JID!
    Route::get('/waiting-list', [WaitingListController::class, 'index'])->name('admin.waitinglist');

    // Input waiting list internal dari sisi kasir admin
    Route::post('/waiting-list', [WaitingListController::class, 'store'])->name('admin.waiting-list.store');

    Route::post('/billing/open/{id}', [BillingController::class, 'openTable'])->name('billing.open');
    Route::post('/billing/move', [BillingController::class, 'moveTable'])->name('billing.move');
    Route::get('/billing/stop/{id}', [BillingController::class, 'stopBilling'])->name('billing.stop');
    Route::post('/billing/mass-open', [BillingController::class, 'massOpenTable'])->name('billing.mass-open');
    Route::get('/billing/active-detail/{table_id}', [BillingController::class, 'getActiveDetail']);

    Route::post('/waiting-list/verify/{id}', [WaitingListController::class, 'verifyPlayer'])->name('admin.waitinglist.verify');
    Route::post('/waiting-list/skip/{id}', [WaitingListController::class, 'skipPlayer'])->name('admin.waitinglist.skip');

    Route::get('/orderfnb', [OrderFnbController::class, 'index'])->name('admin.orderfnb');
    Route::post('/orderfnb', [OrderFnbController::class, 'store'])->name('admin.orderfnb.store');
    Route::get('/orderfnb/current-cart/{table_id}', [OrderFnbController::class, 'getCurrentCart']);
Route::delete('/orderfnb/delete-item/{order_id}', [OrderFnbController::class, 'destroyItem'])->name('admin.orderfnb.delete-item');
Route::get('/orderfnb/active-orders/{table_id}', [OrderFnbController::class, 'getActiveTableOrders']);
});


// =========================================================================
// 5. GROUP ROUTE MASTER (Tetap Menggunakan Pengaman 'role:master')
// =========================================================================
Route::middleware(['auth', 'role:master'])->prefix('master')->group(function () {
    Route::get('/dashboard', [MasterController::class, 'index'])->name('master.dashboard');
    Route::get('/pricing', [MasterController::class, 'pricingIndex'])->name('master.pricing');
    Route::post('/pricing/update/{id}', [MasterController::class, 'pricingUpdate'])->name('master.pricing.update');

    Route::get('/fnb', [MasterController::class, 'fnbIndex'])->name('master.fnb');
    Route::post('/fnb/category', [MasterController::class, 'storeCategory'])->name('master.fnb.category.store');
    Route::post('/fnb/product', [MasterController::class, 'storeProduct'])->name('master.fnb.product.store');
    Route::delete('/fnb/category/{id}', [MasterController::class, 'destroyCategory'])->name('master.fnb.category.destroy');
    Route::post('/fnb/product/update/{id}', [MasterController::class, 'updateProduct'])->name('master.fnb.product.update');
    Route::delete('/fnb/product/delete/{id}', [MasterController::class, 'destroyProduct'])->name('master.fnb.product.destroy');

    Route::post('/packages/store', [MasterController::class, 'packageStore'])->name('master.package.store');
    Route::post('/packages/update/{id}', [MasterController::class, 'packageUpdate'])->name('master.package.update');
    Route::delete('/packages/destroy/{id}', [MasterController::class, 'packageDestroy'])->name('master.package.destroy');

    Route::get('/tables', [MasterController::class, 'tableIndex'])->name('master.tables');
    Route::post('/tables/maintenance/{id}', [MasterController::class, 'toggleMaintenance'])->name('master.tables.maintenance');

    Route::get('/users', [UserController::class, 'index'])->name('master.users');
    Route::post('/users/store', [UserController::class, 'store'])->name('master.users.store');
    Route::post('/users/update/{id}', [UserController::class, 'update'])->name('master.users.update');
    Route::delete('/users/delete/{id}', [UserController::class, 'destroy'])->name('master.users.destroy');

    Route::get('/waiting-list/setting', [MasterController::class, 'waitingListSetting'])->name('master.wlsetting');
    Route::post('/waiting-list/setting/update', [MasterController::class, 'updateWaitingListSetting'])->name('master.waitinglist.update');

    Route::get('/waiting-list', [WaitingListController::class, 'index'])->name('master.waiting-list');
});

// Rute khusus untuk dibaca oleh Python di komputer kasir toko
Route::get('/status-lampu-iot', function() {
    try {
        $now = now();
        $tables = \App\Models\PoolTable::orderBy('table_number', 'asc')->get();
        $statusLampu = [];
        foreach ($tables as $table) {
            if ($table->status === 'playing') {
                $activeTransaction = \App\Models\Transaction::where('pool_table_id', $table->id)->where('status', 'running')->first();
                if ($activeTransaction && $activeTransaction->end_time) {
                    $endTime = \Carbon\Carbon::parse($activeTransaction->end_time);
                    if ($now->greaterThanOrEqualTo($endTime)) {
                        $table->status = 'timeout';
                        $table->save();
                    }
                }
            }
            switch ($table->status) {
                case 'playing': case 'nearly': case 'personal':
                    $statusLampu[$table->table_number] = 'ON';
                    break;
                case 'available': case 'timeout': case 'maintenance': default:
                    $statusLampu[$table->table_number] = 'OFF';
                    break;
            }
        }
        return response()->json($statusLampu);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
