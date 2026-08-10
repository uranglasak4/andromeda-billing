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

        $transactionId = null;

        if ($request->order_type === 'standalone') {
            // Hitung total harga transaksi FnB Standalone terlebih dahulu
            $totalFnbPrice = 0;
            foreach ($request->items as $item) {
                if (isset($item['is_package_include']) && $item['is_package_include']) continue;
                $product = FnbProduct::find($item['id']);
                if ($product) {
                    $totalFnbPrice += (int)$item['stock'] * (float)$product->price;
                }
            }

            $payAmount = (int) ($request->pay_amount ?? $totalFnbPrice);
            $changeAmount = max(0, $payAmount - $totalFnbPrice);

            // Buat transaksi khusus FnB standalone dengan skema kolom baru
            $transaction = Transaction::create([
                'created_by' => auth()->id() ?? 1,
                'closed_by' => auth()->id() ?? 1,
                'pool_table_id' => null,
                'customer_name' => strtoupper($request->customer_name ?? 'WALK-IN'),
                'billing_type' => 'fnb_standalone',
                'start_time' => now(),
                'end_time' => now(),
                'duration' => 0,
                'bill_price' => 0,
                'fnb_price' => $totalFnbPrice,
                'grand_total' => $totalFnbPrice,
                'payment_method' => $request->payment_method ?? 'cash',
                'pay_amount' => $payAmount,
                'change_amount' => $changeAmount,
                'status' => 'completed',
            ]);
            $transactionId = $transaction->id;
        } else {
            $transaction = Transaction::findOrFail($request->transaction_id);
            $transactionId = $transaction->id;
        }

        foreach ($request->items as $item) {
            if (isset($item['is_package_include']) && $item['is_package_include']) {
                continue;
            }

            $product = FnbProduct::find($item['id']);
            if (!$product) continue;

            $inputQty = (int) $item['stock'];
            $price = (float) $product->price;

            if ($request->order_type === 'table') {
                $existingOrder = OrderFnb::where('transaction_id', $transaction->id)
                    ->where('fnb_product_id', $product->id)
                    ->where('price', '>', 0)
                    ->first();

                if ($existingOrder) {
                    // Gunakan $existingOrder->stock agar konsisten
                    $qtyDiff = $inputQty - $existingOrder->stock;
                    $existingOrder->update([
                        'stock' => $inputQty,
                        'subtotal' => $inputQty * $price
                    ]);
                    if ($qtyDiff > 0) {
                        $product->decrement('stock', $qtyDiff);
                    } elseif ($qtyDiff < 0) {
                        $product->increment('stock', abs($qtyDiff));
                    }
                } else {
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
            } else {
                // Standalone Order (Langsung Lunas / Paid)
                OrderFnb::create([
                    'transaction_id' => $transaction->id,
                    'fnb_product_id' => $product->id,
                    'customer_name' => $transaction->customer_name,
                    'stock' => $inputQty,
                    'price' => $price,
                    'subtotal' => $inputQty * $price,
                    'payment_status' => 'paid'
                ]);
                $product->decrement('stock', $inputQty);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pesanan FnB berhasil diproses',
            'transaction_id' => $transactionId
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
