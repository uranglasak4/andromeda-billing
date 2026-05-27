<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FnbProduct;
use App\Models\Transaction;
use App\Models\OrderFnb;
use App\Models\FnbCategory; // Pastikan model kategori di-import jika ada

class OrderFnbController extends Controller
{
    public function index()
    {
        // 1. Ambil semua menu makanan beserta ID kategorinya
        $products = FnbProduct::where('stock', '>', 0)->get();

        // 2. Ambil semua kategori untuk tombol filter di atas
        // Jika nama modelmu bukan FnbCategory, sesuaikan dengan nama model kategori di projectmu
        $categories = \App\Models\FnbCategory::orderBy('name', 'asc')->get();

        // 3. Ambil list transaksi meja yang sedang 'running'
        $activeTransactions = Transaction::with('poolTable')
            ->where('status', 'running')
            ->get();

        // 4. Ambil riwayat penjualan FnB
        $recentOrders = OrderFnb::with('fnbProduct')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.orderfnb', compact('products', 'categories', 'activeTransactions', 'recentOrders'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'order_type' => 'required|in:table,standalone',
                'items' => 'required|array|min:1',
                'items.*.id' => 'required|exists:fnb_products,id',
                'items.*.qty' => 'required|integer|min:1',
            ]);

            if ($request->order_type == 'table') {
                $request->validate(['transaction_id' => 'required|exists:transactions,id']);
            } else {
                $request->validate(['customer_name' => 'required|string|max:100']);
            }

            foreach ($request->items as $item) {
                $product = FnbProduct::findOrFail($item['id']);
                $qty = (int)$item['qty'];
                $subtotal = $product->price * $qty;

                if ($request->order_type == 'table') {
                    $existingOrder = OrderFnb::where('transaction_id', $request->transaction_id)
                        ->where('fnb_product_id', $product->id)
                        ->where('payment_status', 'unpaid')
                        ->first();

                    if ($existingOrder) {
                        $newQty = $existingOrder->qty + $qty;
                        $existingOrder->update([
                            'qty' => $newQty,
                            'subtotal' => $existingOrder->price * $newQty
                        ]);
                    } else {
                        OrderFnb::create([
                            'transaction_id' => $request->transaction_id,
                            'fnb_product_id' => $product->id,
                            'qty' => $qty,
                            'price' => $product->price,
                            'subtotal' => $subtotal,
                            'payment_status' => 'unpaid'
                        ]);
                    }
                } else {
                    OrderFnb::create([
                        'transaction_id' => null,
                        'fnb_product_id' => $product->id,
                        'customer_name' => $request->customer_name,
                        'qty' => $qty,
                        'price' => $product->price,
                        'subtotal' => $subtotal,
                        'payment_status' => 'paid'
                    ]);
                }

                // Potong stok produk
                $product->decrement('stock', $qty);
            }

            return response()->json([
                'success' => true,
                'message' => "Berhasil memproses pesanan makanan!"
            ]);

        } catch (\Exception $e) {
            // Log error agar jika ada masalah bisa kita lacak di storage/logs/laravel.log
            \Log::error("POS FnB Error: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function fnbProduct()
{
    return $this->belongsTo(FnbProduct::class, 'fnb_product_id');
}
}
