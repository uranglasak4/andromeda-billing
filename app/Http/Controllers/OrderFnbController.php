<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FnbProduct;
use App\Models\Transaction;
use App\Models\OrderFnb;
use App\Models\FnbCategory;

class OrderFnbController extends Controller
{
    public function index()
    {
        $products = FnbProduct::where('stock', '>', 0)->get();
        $categories = FnbCategory::orderBy('name', 'asc')->get();

        $activeTransactions = Transaction::with('poolTable')
            ->join('pool_tables', 'transactions.pool_table_id', '=', 'pool_tables.id')
            ->where('transactions.status', 'running')
            ->select('transactions.*')
            ->orderBy(\DB::raw('CAST(pool_tables.table_number AS UNSIGNED)'), 'ASC')
            ->get();

        $recentOrders = OrderFnb::with('fnbProduct')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.orderfnb', compact('products', 'categories', 'activeTransactions', 'recentOrders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_type' => 'required|in:table,standalone',
            'transaction_id' => 'required_if:order_type,table',
            'items' => 'required|array',
        ]);

        $transaction = Transaction::find($request->transaction_id);

        foreach ($request->items as $item) {
            // Abaikan item paket bawaan (Rp 0) agar tidak di-update ulang
            if (isset($item['is_package_include']) && $item['is_package_include']) {
                continue;
            }

            $product = FnbProduct::find($item['id']);
            if (!$product)
                continue;

            $inputQty = (int) $item['stock'];
            $price = (float) $product->price;

            // Cari apakah pesanan FnB berbayar untuk produk ini sudah ada di transaksi
            $existingOrder = OrderFnb::where('transaction_id', $transaction->id)
                ->where('fnb_product_id', $product->id)
                ->where('price', '>', 0)
                ->first();

            if ($existingOrder) {
                // Jika sudah ada sebelumnya, ganti qty-nya dengan qty terbaru dari keranjang
                $qtyDiff = $inputQty - $existingOrder->qty;

                $existingOrder->update([
                    'stock' => $inputQty,
                    'subtotal' => $inputQty * $price
                ]);

                // Potong stok produk sesuai selisihnya saja
                if ($qtyDiff > 0) {
                    $product->decrement('stock', $qtyDiff);
                }
            } else {
                // Buat record pesanan berbayar baru
                OrderFnb::create([
                    'transaction_id' => $transaction->id,
                    'fnb_product_id' => $product->id,
                    'customer_name' => $transaction->customer_name,
                    'stock' => $inputQty,
                    'price' => $price,
                    'subtotal' => $inputQty * $price,
                    'payment_status' => 'unpaid'
                ]);

                $product->decrement('stock', $inputQty);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan FnB berhasil diperbarui'
        ]);
    }

    public function getCurrentCart($table_id)
    {
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
            $isIncludePackage = ((int) $order->price === 0);

            return [
                'id' => $order->fnb_product_id,
                'order_id' => $order->id,
                'name' => ($order->fnbProduct->name ?? 'Menu') . ($isIncludePackage ? ' (Include Paket)' : ''),
                'price' => (int) $order->price,
                'stock' => (int) $order->stock,
                'subtotal' => (int) $order->subtotal,
                'is_package_include' => $isIncludePackage
            ];
        });

        return response()->json($cartItems);
    }

    public function destroyItem($order_id)
    {
        try {
            $order = OrderFnb::findOrFail($order_id);

            // Cegah penghapusan jika item berharga 0 (bawaan paket)
            if ((int) $order->price === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item bawaan paket tidak dapat dihapus!'
                ], 422);
            }

            if ($order->fnbProduct) {
                $order->fnbProduct->increment('stock', $order->stock);
            }

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
                ->where('status', 'running')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => true,
                    'items' => []
                ]);
            }

            $orders = OrderFnb::with('fnbProduct')
                ->where('transaction_id', $transaction->id)
                ->where('payment_status', 'unpaid')
                ->get();

            $formattedItems = $orders->map(function ($order) {
                $isIncludePackage = ((int) $order->price === 0);

                return [
                    'id' => $order->fnb_product_id,
                    'order_id' => $order->id,
                    'name' => ($order->fnbProduct->name ?? 'Produk Terhapus') . ($isIncludePackage ? ' (Include Paket)' : ''),
                    'price' => (int) $order->price,
                    'stock' => (int) $order->stock,
                    'is_package_include' => $isIncludePackage
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
