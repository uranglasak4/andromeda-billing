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

        // 1. Definisikan variabel awal untuk transaksi meja
        $transaction = null;

        if ($request->order_type == 'table') {
            $request->validate(['transaction_id' => 'required|exists:transactions,id']);

            // AMBIL DATA TRANSAKSI BERDASARKAN ID YANG DIKIRIM FRONTEND!
            $transaction = Transaction::find($request->transaction_id);
        } else {
            $request->validate(['customer_name' => 'required|string|max:100']);
        }

        foreach ($request->items as $item) {
            $product = FnbProduct::find($item['id']);
            if (!$product) continue;

            $qty = (int) $item['qty'];
            $subtotal = $product->price * $qty;

            if ($request->order_type === 'table' && $transaction) {
                // Cari baris order lama yang belum lunas untuk menu ini di transaksi terkait
                $existingOrder = OrderFnb::where('transaction_id', $transaction->id)
                    ->where('fnb_product_id', $product->id)
                    ->where('payment_status', 'unpaid')
                    ->first();

                // Hitung selisih stok yang harus dikurangi/dikembalikan
                $oldQty = $existingOrder ? $existingOrder->qty : 0;
                $qtyDifference = $qty - $oldQty;

                if ($qty <= 0) {
                    // Jika dikurangi sampai habis/0 oleh kasir, hapus barisnya dari db
                    if ($existingOrder) {
                        $product->increment('stock', $oldQty); // kembalikan stok utuh
                        $existingOrder->delete();
                    }
                } else {
                    // Update pesanan lama atau buat baru jika belum pernah dipesan
                    OrderFnb::updateOrCreate(
                        [
                            'transaction_id' => $transaction->id,
                            'fnb_product_id' => $product->id,
                            'payment_status' => 'unpaid'
                        ],
                        [
                            'customer_name'  => $transaction->customer_name,
                            'qty'            => $qty,
                            'price'          => $product->price,
                            'subtotal'       => $subtotal
                        ]
                    );

                    // Sesuaikan stok berdasarkan selisih perubahan qty
                    if ($qtyDifference > 0) {
                        $product->decrement('stock', $qtyDifference);
                    } else if ($qtyDifference < 0) {
                        $product->increment('stock', abs($qtyDifference));
                    }
                }
            } else {
                // Untuk orderan Tanpa Meja (Standalone / Takeaway) tetap langsung buat baru
                OrderFnb::create([
                    'transaction_id' => null,
                    'fnb_product_id' => $product->id,
                    'customer_name'  => $request->customer_name,
                    'qty'            => $qty,
                    'price'          => $product->price,
                    'subtotal'       => $subtotal,
                    'payment_status' => 'paid'
                ]);
                $product->decrement('stock', $qty);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil memperbarui pesanan makanan Meja!"
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

public function getCurrentCart($table_id)
{
    // Pastikan mencari berdasarkan pool_table_id dan status 'running' sesuai database billing Anda
    $transaction = Transaction::where('pool_table_id', $table_id)
        ->where('status', 'running')
        ->first();

    if (!$transaction) {
        return response()->json([]);
    }

    $orders = OrderFnb::where('transaction_id', $transaction->id)
        ->where('payment_status', 'unpaid')
        ->with('fnbProduct')
        ->get();

    $cartItems = $orders->map(function ($order) {
        return [
            'id'       => $order->fnb_product_id,
            'order_id' => $order->id,
            'name'     => $order->fnbProduct->name ?? 'Menu',
            'price'    => (int) $order->price,
            'qty'      => (int) $order->qty,
            'subtotal' => (int) $order->subtotal
        ];
    });

    return response()->json($cartItems);
}

// 🚀 FUNGSI BARU: Hapus item FnB yang salah input oleh kasir
public function destroyItem($order_id)
{
    try {
        $order = OrderFnb::findOrFail($order_id);

        // Kembalikan stok produk yang tadi terlanjur dipotong
        if ($order->fnbProduct) {
            $order->fnbProduct->increment('stock', $order->qty);
        }

        // Hapus data orderan salah tersebut dari database
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil dihapus dari meja!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Gagal menghapus pesanan: ' . $e->getMessage()
        ], 500);
    }
}

public function getActiveTableOrders($table_id)
{
    try {
        $transaction = Transaction::where('pool_table_id', $table_id)
            ->where('status', 'running') // Gunakan 'running', bukan 'active'
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => true,
                'items' => []
            ]);
        }

        $orders = OrderFnb::with('fnbProduct') // Gunakan relasi fnbProduct yang benar
            ->where('transaction_id', $transaction->id)
            ->where('payment_status', 'unpaid')
            ->get();

        $formattedItems = $orders->map(function($order) {
            return [
                'id' => $order->fnb_product_id,
                'name' => $order->fnbProduct->name ?? 'Produk Terhapus',
                'price' => (int) $order->price,
                'qty' => (int) $order->qty,
                'image' => null
            ];
        });

        return response()->json([
            'success' => true,
            'items' => $formattedItems
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

}
