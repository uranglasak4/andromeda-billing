<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WaitingListController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\UserController;
use App\Models\PoolTable;
use App\Models\Transaction;

// 1. Halaman Depan untuk Pelanggan (Public)
Route::get('/', function () {
    return view('customer.index'); // Tampilan "Dalam Proses Pengerjaan"
})->middleware('guest');

// 2. Route Login (Hanya bisa diakses jika BELUM login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// 3. Logout
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// 4. Group Route Admin
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/api/tables/status', [AdminController::class, 'getTablesStatus'])->name('api.tables.status');
});

// 5. Group Route master
// Cari bagian ini di web.php dan ubah
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
});

Route::post('/admin/waiting-list', [WaitingListController::class, 'store'])->name('waiting-list.store');
Route::post('/admin/billing/open/{id}', [BillingController::class, 'openTable'])->name('billing.open');
Route::post('/admin/billing/move', [BillingController::class, 'moveTable'])->name('billing.move');
Route::get('/admin/billing/stop/{id}', [BillingController::class, 'stopBilling'])->name('billing.stop');



// Rute khusus untuk dibaca oleh Python di komputer kasir toko
Route::get('/status-lampu-iot', function() {
    try {
        $now = now(); // Ambil waktu server saat ini

        // Ambil semua data meja langsung dari database
        $tables = \App\Models\PoolTable::orderBy('table_number', 'asc')->get();
        $statusLampu = [];

        foreach ($tables as $table) {

            // JIKA meja sedang bermain ('playing'), kita cek waktu aslinya di database
            if ($table->status === 'playing') {
                // Cari transaksi aktif (running) untuk meja ini
                $activeTransaction = \App\Models\Transaction::where('pool_table_id', $table->id)
                                        ->where('status', 'running')
                                        ->first();

                // Jika transaksi memiliki batas waktu (hourly/package) dan waktunya SUDAH HABIS
                if ($activeTransaction && $activeTransaction->end_time) {
                    $endTime = \Carbon\Carbon::parse($activeTransaction->end_time);

                    if ($now->greaterThanOrEqualTo($endTime)) {
                        // 1. Update status meja menjadi 'timeout' secara permanen di database!
                        $table->status = 'timeout';
                        $table->save();
                    }
                }
            }

            // SINKRONISASI TRANSMISI KE PYTHON (6 Status Meja Biliar)
            switch ($table->status) {
                case 'playing':     // Lampu ON
                case 'nearly':      // Lampu ON
                case 'personal':    // Lampu ON
                    $statusLampu[$table->table_number] = 'ON';
                    break;

                case 'available':   // Lampu OFF
                case 'timeout':     // Lampu OFF (Skenario Waktu Habis Anda)
                case 'maintenance': // Lampu OFF
                default:
                    $statusLampu[$table->table_number] = 'OFF';
                    break;
            }
        }

        // Kembalikan response JSON valid untuk dibaca Python
        return response()->json($statusLampu);

    } catch (\Exception $e) {
        // Cegah Python crash jika ada error tak terduga di Laravel
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
