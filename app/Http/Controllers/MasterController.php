<?php

namespace App\Http\Controllers;

use App\Models\PoolTable;
use App\Models\Transaction;
use App\Models\PricingRule;
use Illuminate\Http\Request;
use App\Models\FnbCategory;
use App\Models\FnbProduct;
use App\Models\Package;

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

public function pricingIndex()
{
    $rules = PricingRule::all();
    $packages = Package::all();
    return view('master.pricing', compact('rules', 'packages'));
}

public function pricingUpdate(Request $request, $id)
{
    $rule = PricingRule::findOrFail($id);

    // Ambil data hari, jika kosong kasih string kosong
    $daysArray = $request->input('active_days', []);
    $activeDaysString = implode(',', $daysArray);

    $rule->update([
        'price_per_hour' => $request->price_per_hour,
        'min_charge'     => $request->min_charge,
        'start_time'     => $request->start_time,
        'end_time'       => $request->end_time,
        'active_days'    => $activeDaysString, // Simpan hasil gabungan "1,2,3,4"
    ]);

    return back()->with('success', 'Data berhasil diperbarui!');
}

public function fnbIndex(Request $request)
{
    $search = $request->input('search');
    $categoryFilter = $request->input('category_id');

    // Ambil produk dengan filter search & kategori + Pagination 10
    $products = FnbProduct::with('category')
        ->when($search, function($query) use ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        })
        ->when($categoryFilter, function($query) use ($categoryFilter) {
            $query->where('fnb_category_id', $categoryFilter);
        })
        ->paginate(15, ['*'], 'products_page');

    // Ambil kategori dengan Pagination 5
    $categories = FnbCategory::paginate(5, ['*'], 'categories_page');

    // Semua kategori untuk dropdown filter & input produk
    $allCategories = FnbCategory::all();

    return view('master.fnb', compact('products', 'categories', 'allCategories'));
}

public function storeCategory(Request $request)
{
    $request->validate(['name' => 'required']);
    FnbCategory::create($request->all());
    return back()->with('success', 'Kategori berhasil ditambahkan!');
}

public function storeProduct(Request $request)
{
    $request->validate([
        'fnb_category_id' => 'required',
        'name' => 'required',
        'price' => 'required|numeric',
        'stock' => 'required|numeric'
    ]);

    FnbProduct::create($request->all());
    return back()->with('success', 'Produk berhasil ditambahkan!');
}

public function destroyCategory($id)
{
    FnbCategory::findOrFail($id)->delete();
    return back()->with('success', 'Kategori dan produk di dalamnya berhasil dihapus!');
}

public function updateProduct(Request $request, $id)
{
    $request->validate([
        'fnb_category_id' => 'required',
        'name' => 'required',
        'price' => 'required|numeric',
        'stock' => 'required|numeric'
    ]);

    $product = FnbProduct::findOrFail($id);
    $product->update($request->all());

    return back()->with('success', 'Produk berhasil diperbarui!');
}

public function packageStore(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'day_type' => 'required|in:weekday,weekend,both',
            'active_from' => 'required',
            'active_to' => 'required',
            'duration_type' => 'required|in:minutes,fixed_end_time',
            'duration_value' => 'required'
        ]);

        \App\Models\Package::create($request->all());

        return back()->with('success', 'Paket promo berhasil ditambahkan!');
    }

    public function packageUpdate(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'day_type' => 'required|in:weekday,weekend,both',
            'active_from' => 'required',
            'active_to' => 'required',
            'duration_type' => 'required|in:minutes,fixed_end_time',
            'duration_value' => 'required'
        ]);

        $package = \App\Models\Package::findOrFail($id);
        $package->update($request->all());

        return back()->with('success', 'Paket promo berhasil diperbarui!');
    }

    public function packageDestroy($id)
    {
        \App\Models\Package::findOrFail($id)->delete();

        return back()->with('success', 'Paket promo berhasil dihapus!');
    }

    public function tableIndex()
{
    $tables = PoolTable::all();
    return view('master.tables', compact('tables'));
}

public function toggleMaintenance($id)
{
    $table = PoolTable::findOrFail($id);

    // KUNCI KEAMANAN: Hanya boleh ubah jika status 'available' atau 'maintenance'
    if (!in_array($table->status, ['available', 'maintenance'])) {
        return back()->with('error', 'Meja sedang digunakan transaksi! Status tidak bisa diubah ke maintenance.');
    }

    // Toggle statusnya
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
}
