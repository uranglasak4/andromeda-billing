<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WaitingListController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\MasterController;

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
});

Route::post('/admin/waiting-list', [WaitingListController::class, 'store'])->name('waiting-list.store');
Route::post('/admin/billing/open/{id}', [BillingController::class, 'openTable'])->name('billing.open');
Route::post('/admin/billing/move', [BillingController::class, 'moveTable'])->name('billing.move');
Route::get('/admin/billing/stop/{id}', [BillingController::class, 'stopBilling'])->name('billing.stop');
