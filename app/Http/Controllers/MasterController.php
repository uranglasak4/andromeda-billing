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
        $rule->update([
            'price_per_hour' => $request->price_per_hour
        ]);

        return back()->with('success', 'Harga reguler berhasil diperbarui!');
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
        Package::create($request->all());
        return back()->with('success', 'Paket promo baru berhasil ditambahkan!');
    }

    public function packageUpdate(Request $request, $id)
    {
        $package = Package::findOrFail($id);
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
}
