<?php

namespace App\Http\Controllers;

use App\Models\PoolTable;
use App\Models\Transaction;
use App\Models\PricingRule;
use Illuminate\Http\Request;
use App\Models\FnbCategory;
use App\Models\FnbProduct;
use App\Models\Package;
use App\Models\Setting;


class MasterController extends Controller
{
    public function index()
    {
        // 1. Ambil semua data meja (tanpa eager loading transaksi aktif)
        $tables = PoolTable::orderBy('table_number', 'asc')->get();

        // 2. Hitung statistik riwayat transaksi meja khusus HARI INI
        foreach ($tables as $table) {
            $historyToday = $table->transactions()
                ->where('status', 'finished')
                ->whereDate('end_time', today());

            $table->total_transaksi = $historyToday->count();
            $table->total_pendapatan = $historyToday->sum('grand_total');
            $table->total_waktu = $historyToday->sum('duration');
        }

        // 3. Omzet total hari ini
        $omzetHariIni = Transaction::where('status', 'finished')
            ->whereDate('end_time', today())
            ->sum('grand_total');

        // 4. Hitung jumlah meja yang sedang terisi
        $mejaTerisi = $tables->whereIn('status', ['playing', 'personal'])->count();
        $waitingLists = \App\Models\WaitingList::where('status', 'waiting')->get();

        // 5. Breakdown Billing & FnB
        $billingHariIni = Transaction::where('status', 'finished')
            ->whereDate('end_time', today())
            ->sum('bill_price');

        $fnbHariIni = Transaction::where('status', 'finished')
            ->whereDate('end_time', today())
            ->sum('fnb_price');

        // 6. Ambil Kasir Shift Aktif saat ini
        $activeTrxToday = Transaction::whereIn('status', ['running', 'active'])
            ->whereDate('start_time', today())
            ->latest()
            ->first();

        $activeCashier = $activeTrxToday ? \App\Models\User::find($activeTrxToday->created_by) : null;

        // 7. Data Grafik Omzet 7 Hari Terakhir
        $chartDates = [];
        $chartOmset = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = \Carbon\Carbon::today()->subDays($i);
            $chartDates[] = $date->format('d M');
            $chartOmset[] = Transaction::where('status', 'finished')
                ->whereDate('end_time', $date)
                ->sum('grand_total');
        }

        return view('master.dashboardmaster', compact(
            'tables',
            'omzetHariIni',
            'mejaTerisi',
            'waitingLists',
            'billingHariIni',
            'fnbHariIni',
            'activeCashier',
            'chartDates',
            'chartOmset'
        ));
    }

    public function pricingIndex()
    {
        $rules = PricingRule::all();
        $packages = Package::with('fnbProducts')->get(); // Mengambil paket beserta relasi FnB-nya

        // 🔴 KUNCI PERBAIKAN: Ambil semua data produk FnB dari database
        $allFnbProducts = \App\Models\FnbProduct::orderBy('name', 'asc')->get();

        // Passing variabel $allFnbProducts ke view pricing.blade.php
        return view('master.pricing', compact('rules', 'packages', 'allFnbProducts'));
    }

    public function pricingUpdate(Request $request, $id)
    {
        // 1. Validasi input yang masuk dari form modal Anda
        $request->validate([
            'price' => 'required|integer|min:0',
            'min_charge' => 'required|integer|min:0',
            'start_time' => 'required',
            'end_time' => 'required',
            'active_days' => 'required|array', // Memastikan hari aktif dikirim berupa array
        ]);

        try {
            $rule = PricingRule::findOrFail($id);

            // 2. Jembatan Krusial: Ubah Array [1, 2, 3] menjadi String "1,2,3" agar bisa masuk database
            $activeDaysString = implode(',', $request->active_days);

            // 3. Update data langsung ke kolom database Anda masing-masing
            $rule->update([
                'price_per_hour' => $request->price,      // Menyimpan input 'price' ke kolom 'price_per_hour'
                'min_charge' => $request->min_charge, // Menyimpan input 'min_charge'
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'active_days' => $activeDaysString,    // Menyimpan string hasil konversi implode
            ]);

            // Kembali dengan notifikasi sukses
            return redirect()->back()->with('success', 'Aturan harga reguler billing berhasil diperbarui!');

        } catch (\Exception $e) {
            // Jika ada kegagalan, tangkap pesan errornya agar terlihat jelas apa yang bermasalah
            return redirect()->back()->with('error', 'Gagal memperbarui harga: ' . $e->getMessage());
        }
    }

    // --- MANAJEMEN FnB FIXED PAGINATION & DROPDOWN FILTER ---
    public function fnbIndex(Request $request)
    {
        // KUNCI UTAMA: Ambil SEMUA kategori tanpa paginate khusus untuk Dropdown Filter produk
        $dropdownCategories = FnbCategory::orderBy('name', 'asc')->get();

        // Ambil Kategori Ter-paginate (8 entri per halaman) khusus untuk Tabel Kategori di bawah
        $allCategories = FnbCategory::withCount('products')
            ->paginate(8, ['*'], 'categories_page')
            ->withQueryString();

        // Ambil Query Server-Side Filter & Search untuk Produk FnB
        $query = FnbProduct::with('category');

        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('fnb_category_id', $request->category_id);
        }

        if ($request->has('search') && $request->search != '') {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Set server-side pagination 14 produk per halaman sesuai permintaan
        $products = $query->paginate(14, ['*'], 'products_page')->withQueryString();

        return view('master.fnb', compact('products', 'allCategories', 'dropdownCategories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        FnbCategory::create(['name' => $request->name]);
        return back()->with('success', 'Kategori baru berhasil ditambahkan!');
    }

    public function destroyCategory($id)
    {
        FnbCategory::findOrFail($id)->delete();
        return back()->with('success', 'Kategori berhasil dihapus!');
    }

    public function storeProduct(Request $request)
    {
        $request->validate([
            'fnb_category_id' => 'required|exists:fnb_categories,id',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'hpp' => 'required|numeric',
            'stock' => 'required|integer',
            'min_stock' => 'required|integer',
        ]);

        FnbProduct::create($request->all());
        return back()->with('success', 'Produk baru berhasil ditambahkan!');
    }

    public function updateProduct(Request $request, $id)
    {
        $product = FnbProduct::findOrFail($id);
        $product->update($request->all());
        return redirect()->route('master.fnb')->with('success', 'Produk berhasil diperbarui!');
    }

    public function destroyProduct($id)
    {
        FnbProduct::findOrFail($id)->delete();
        return redirect()->route('master.fnb')->with('success', 'Produk berhasil dihapus!');
    }

    public function packageStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'day_type' => 'required',
            'active_from' => 'required',
            'active_to' => 'required',
            'duration_type' => 'required',
            'duration_value' => 'required',
        ]);

        // 1. Simpan Data Paket (menggunakan active_from dan active_to sesuai Form Blade)
        $package = Package::create([
            'name' => $request->name,
            'price' => $request->price,
            'day_type' => $request->day_type,
            'active_from' => $request->active_from,
            'active_to' => $request->active_to,
            'duration_type' => $request->duration_type,
            'duration_value' => $request->duration_value,
        ]);

        // 2. Simpan Produk FnB Include (Jika Ada)
        if ($request->has('fnb_products')) {
            foreach ($request->fnb_products as $index => $fnbId) {
                if (!empty($fnbId)) {
                    $stock = $request->fnb_quantities[$index] ?? 1;
                    $package->fnbProducts()->attach($fnbId, ['stock' => $stock]);
                }
            }
        }

        return back()->with('success', 'Paket billing berhasil dibuat!');
    }

    public function packageUpdate(Request $request, $id)
    {
        $package = Package::findOrFail($id);

        // 1. Update Informasi Paket
        $package->update([
            'name' => $request->name,
            'price' => $request->price,
            'day_type' => $request->day_type,
            'active_from' => $request->active_from,
            'active_to' => $request->active_to,
            'duration_type' => $request->duration_type,
            'duration_value' => $request->duration_value,
        ]);

        // 2. Sync / Perbarui Produk FnB Include
        $syncData = [];
        if ($request->has('fnb_products')) {
            foreach ($request->fnb_products as $index => $fnbId) {
                if (!empty($fnbId)) {
                    $stock = $request->fnb_quantities[$index] ?? 1;
                    $syncData[$fnbId] = ['stock' => $stock];
                }
            }
        }

        // sync() akan otomatis menambah, merubah stock, atau menghapus FnB yang dilepas
        $package->fnbProducts()->sync($syncData);

        return back()->with('success', 'Paket billing berhasil diperbarui!');
    }

    public function packageDestroy($id)
    {
        \App\Models\Package::findOrFail($id)->delete();
        return back()->with('success', 'Paket promo berhasil dihapus!');
    }

    public function tableIndex()
    {
        $tables = PoolTable::orderBy('table_number', 'asc')->get();
        // 🗑️ Tambahkan baris ini untuk mengambil data meja yang di-soft delete
        $trashedTables = PoolTable::onlyTrashed()->orderBy('table_number', 'asc')->get();
        $nearlyWarningMinutes = Setting::where('key', 'nearly_warning_minutes')->value('value') ?? 20;

        return view('master.tables', compact('tables', 'trashedTables', 'nearlyWarningMinutes'));
    }

    // --- METHOD TAMBAH MEJA BARU (MASTER) ---
    // --- METHOD TAMBAH MEJA BARU (MASTER) ---
    // --- METHOD TAMBAH MEJA BARU (MURNI CREATE BARU) ---
    public function storeTable(Request $request)
    {
        $request->validate([
            'table_number' => 'required|numeric|min:1',
            'relay_channel' => 'required|numeric|min:1|max:' . env('MAX_RELAY_CHANNELS', 16),
        ]);

        // 1. Cek apakah Nomor Meja atau Relay Channel sedang DIPAKAI MEJA AKTIF
        $activeExist = PoolTable::whereNull('deleted_at')
            ->where(function ($q) use ($request) {
                $q->where('table_number', $request->table_number)
                    ->orWhere('relay_channel', $request->relay_channel);
            })
            ->exists();

        if ($activeExist) {
            return redirect()->back()->with('error', 'Gagal! Nomor Meja atau Relay Channel sudah digunakan oleh meja aktif lain.');
        }

        // 2. Cek apakah Nomor Meja atau Relay Channel ADA DI RECYCLE BIN (SAMPAH)
        $trashedExist = PoolTable::onlyTrashed()
            ->where(function ($q) use ($request) {
                $q->where('table_number', $request->table_number)
                    ->orWhere('relay_channel', $request->relay_channel);
            })
            ->exists();

        if ($trashedExist) {
            return redirect()->back()->with('error', 'Gagal! Nomor Meja atau Relay Channel ini ada di Recycle Bin. Silakan Restore data lama dari Recycle Bin.');
        }

        // 3. Jika benar-benar bersih, buat baris data baru di database
        try {
            PoolTable::create([
                'table_number' => $request->table_number,
                'relay_channel' => $request->relay_channel,
                'status' => 'available',
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan pada database: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Meja {$request->table_number} berhasil ditambahkan!");
    }

    // --- METHOD EDIT/UPDATE MEJA (MASTER) ---
    public function updateTable(Request $request, $id)
    {
        $table = PoolTable::findOrFail($id);

        // 1. Cek proteksi jika meja sedang aktif digunakan
        if (in_array($table->status, ['playing', 'personal', 'nearly'])) {
            return redirect()->back()->with('error', 'Gagal memperbarui! Meja ini sedang digunakan dalam transaksi aktif.');
        }

        // 2. Validasi Input Form
        $request->validate([
            'table_number' => 'required|numeric|min:1',
            'relay_channel' => 'required|numeric|min:1|max:' . env('MAX_RELAY_CHANNELS', 16),
        ], [
            'table_number.required' => 'Nomor meja wajib diisi!',
            'relay_channel.max' => 'Relay channel melebihi kapasitas hardware (' . env('MAX_RELAY_CHANNELS', 16) . ')!',
        ]);

        // 3. SOLUSI 1: Cek apakah nomor/relay digunakan oleh MEJA AKTIF LAIN
        $tableExist = PoolTable::where('id', '!=', $id)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($request) {
                $query->where('table_number', $request->table_number)
                    ->orWhere('relay_channel', $request->relay_channel);
            })
            ->exists();

        if ($tableExist) {
            return redirect()->back()->with('error', 'Nomor meja atau Relay Channel sudah digunakan oleh meja aktif lain!');
        }

        // 4. PENAMBAHAN BARU: Cek apakah nomor/relay terikat pada MEJA DI RECYCLE BIN (TRASH)
        $trashedExist = PoolTable::onlyTrashed()
            ->where(function ($query) use ($request) {
                $query->where('table_number', $request->table_number)
                    ->orWhere('relay_channel', $request->relay_channel);
            })
            ->exists();

        if ($trashedExist) {
            return redirect()->back()->with('error', 'Gagal Edit! Nomor Meja atau Relay Channel tersebut ada di Recycle Bin. Silakan gunakan nomor lain yang belum terdaftar atau Restore meja dari Recycle Bin.');
        }

        // 5. Update Data Meja (dengan try-catch untuk jaring pengaman terakhir dari Unique Constraint Database)
        try {
            $table->update([
                'table_number' => $request->table_number,
                'relay_channel' => $request->relay_channel,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return redirect()->back()->with('error', 'Gagal Update! Relay Channel atau Nomor Meja ini mengalami duplikasi pada database.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', "Data Meja {$request->table_number} berhasil diperbarui!");
    }

    // --- METHOD RECYCLE BIN / SAMPAN MEJA ---
    public function tableTrash()
    {
        $trashedTables = PoolTable::onlyTrashed()->orderBy('table_number', 'asc')->get();
        return view('master.tables_trash', compact('trashedTables'));
    }

    // --- METHOD RESTORE MEJA DARI RECYCLE BIN ---
    public function restoreTable($id)
    {
        $table = PoolTable::onlyTrashed()->findOrFail($id);

        // Cek apakah nomor/relay meja sampah ini sedang dipakai meja AKTIF
        $activeExist = PoolTable::whereNull('deleted_at')
            ->where(function ($q) use ($table) {
                $q->where('table_number', $table->table_number)
                    ->orWhere('relay_channel', $table->relay_channel);
            })
            ->exists();

        if ($activeExist) {
            return redirect()->back()->with('error', "Gagal Restore! Nomor Meja {$table->table_number} atau Relay Channel {$table->relay_channel} sedang digunakan oleh meja aktif.");
        }

        $table->restore();
        $table->update(['status' => 'available', 'is_active' => true]);

        return redirect()->back()->with('success', "Meja {$table->table_number} berhasil dipulihkan!");
    }

    // --- METHOD HAPUS MEJA (MASTER) ---
    public function destroyTable($id)
    {
        $table = PoolTable::findOrFail($id);

        // Tetap cegah jika meja sedang aktif dipakai bermain
        if (in_array($table->status, ['playing', 'personal', 'nearly'])) {
            return redirect()->back()->with('error', 'Gagal menghapus! Meja ini sedang digunakan dalam transaksi aktif.');
        }

        $tableNumber = $table->table_number;

        // Menghapus meja secara soft delete (hanya mengisi kolom deleted_at)
        $table->delete();

        return redirect()->back()->with('success', "Meja {$tableNumber} berhasil dihapus dari sistem!");
    }

    public function updateNearlySetting(Request $request)
    {
        $request->validate([
            'nearly_warning_minutes' => 'required|integer|min:1|max:60',
        ]);

        Setting::updateOrCreate(
            ['key' => 'nearly_warning_minutes'],
            ['value' => $request->nearly_warning_minutes]
        );

        return redirect()->back()->with('success', 'Konfigurasi peringatan Nearly berhasil disimpan! Lampu akan berkedip saat sisa ' . $request->nearly_warning_minutes . ' menit.');
    }

    public function toggleMaintenance($id)
    {
        $table = PoolTable::findOrFail($id);

        if (!in_array($table->status, ['available', 'maintenance'])) {
            return back()->with('error', 'Meja sedang digunakan transaksi! Status tidak bisa diubah ke maintenance.');
        }

        if ($table->status === 'available') {
            $table->status = 'maintenance';
            $pesan = 'Meja berhasil di-set ke MAINTENANCE.';
        } else {
            $table->status = 'available';
            $pesan = 'Meja berhasil dikembalikan ke AVAILABLE.';
        }

        $table->save();
        return back()->with('success', $pesan);
    }

    public function waitingListSetting()
    {
        $verificationTime = Setting::where('key', 'verification_time')->value('value') ?? 15;
        $maxOnlineQueue = Setting::where('key', 'max_online_queue')->value('value') ?? 10;

        // Ambil Seluruh Antrean Hari Ini
        $allWaitingLists = \App\Models\WaitingList::whereDate('created_at', \Carbon\Carbon::today())
            ->orderBy('created_at', 'asc')
            ->get();

        // Filter Data Masing-Masing Tab
        $waitingLists = $allWaitingLists->filter(function ($item) {
            return in_array(strtolower($item->status), ['waiting', 'not_verified', 'verified', 'call']);
        });

        $tabOnsite = $allWaitingLists->filter(function ($item) {
            return strtolower($item->tipe) === 'onsite' && in_array(strtolower($item->status), ['waiting', 'call']);
        });

        $tabOnlineVerified = $allWaitingLists->filter(function ($item) {
            return strtolower($item->tipe) === 'online' && in_array(strtolower($item->status), ['verified', 'call']);
        });

        $tabOnlineUnverified = $allWaitingLists->filter(function ($item) {
            return strtolower($item->tipe) === 'online' && strtolower($item->status) === 'not_verified';
        });

        $tabNoShow = $allWaitingLists->filter(function ($item) {
            return strtolower($item->status) === 'no_show';
        });

        $tabExpired = $allWaitingLists->filter(function ($item) {
            return strtolower($item->status) === 'expired';
        });

        $tabFailed = $allWaitingLists->filter(function ($item) {
            return in_array(strtolower($item->status), ['failed', 'gagal']);
        });

        $tabDone = $allWaitingLists->filter(function ($item) {
            return in_array(strtolower($item->status), ['done', 'check_in', 'completed', 'selesai']);
        });

        return view('master.wlsetting', compact(
            'verificationTime',
            'maxOnlineQueue',
            'waitingLists',
            'tabOnsite',
            'tabOnlineVerified',
            'tabOnlineUnverified',
            'tabNoShow',
            'tabExpired',
            'tabFailed',
            'tabDone'
        ));
    }

    // Fungsi menyimpan/mengupdate konfigurasi dari form Master
    public function updateWaitingListSetting(Request $request)
    {
        $request->validate([
            'verification_time' => 'required|integer|min:1',
            'max_online_queue' => 'required|integer|min:1',
        ]);

        // Update atau Buat jika key belum ada di tabel settings
        Setting::updateOrCreate(['key' => 'verification_time'], ['value' => $request->verification_time]);
        Setting::updateOrCreate(['key' => 'max_online_queue'], ['value' => $request->max_online_queue]);

        return redirect()->back()->with('success', 'Konfigurasi Waiting List berhasil diperbarui oleh Master!');
    }
}
