<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function adminIndex(Request $request)
    {
        // 1. Ambil Tanggal Default (Hari Ini)
        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate   = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        // 2. Logika Default Kasir
        if ($request->has('cashier_id')) {
            $cashierId = $request->get('cashier_id') === 'all' ? null : $request->get('cashier_id');
        } else {
            $cashierId = auth()->id();
        }

        // 3. Base Query Transaksi Selesai (status: finished)
        $query = Transaction::where('status', 'finished')
            ->whereBetween('end_time', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);

        // 4. Filter berdasarkan Kasir Close (closed_by)
        if (!empty($cashierId)) {
            $query->where('closed_by', $cashierId);
        }

        // 5. Filter Kategori Transaksi (BLM / FNB / ALL)
        if ($request->filled('type') && $request->get('type') !== 'all') {
            $type = $request->get('type');

            if ($type === 'blm') {
                $query->where('bill_price', '>', 0)->where('fnb_price', 0);
            } elseif ($type === 'fnb') {
                $query->where('bill_price', 0)->where('fnb_price', '>', 0);
            } elseif ($type === 'billing_fnb') {
                $query->where('bill_price', '>', 0)->where('fnb_price', '>', 0);
            }
        }

        // 6. Logika Pencarian (Fixed: Hanya cek table_number pada poolTable)
        if ($request->filled('search')) {
            $search = trim($request->get('search'));

            // Konversi "0003" menjadi angka 3 untuk cari langsung ke ID
            $searchId = ltrim($search, '0');

            $query->where(function($q) use ($search, $searchId) {
                // A. Cari berdasarkan angka ID murni
                if (!empty($searchId) && is_numeric($searchId)) {
                    $q->orWhere('id', $searchId);
                }

                // B. Cari ID 4 digit (misal ID 3 dibaca '0003')
                $q->orWhereRaw("LPAD(id, 4, '0') LIKE ?", ["%{$search}%"])

                  // C. Cari berdasarkan Nama Customer
                  ->orWhere('customer_name', 'like', "%{$search}%")

                  // D. Cari berdasarkan Nomor Meja via Relasi poolTable
                  ->orWhereHas('poolTable', function($tableQuery) use ($search) {
                      $tableQuery->where('table_number', 'like', "%{$search}%");
                  });
            });
        }

        // 7. Ambil Daftar Kasir (Role Admin)
        $cashiers = User::where('role', 'admin')->orderBy('name', 'asc')->get();

        // 8. Hitung Total Ringkasan
        $totalOmset        = (clone $query)->sum('grand_total');
        $totalCash         = (clone $query)->where('payment_method', 'cash')->sum('grand_total');
        $totalNonCash      = (clone $query)->where('payment_method', '!=', 'cash')->sum('grand_total');
        $totalTransactions = (clone $query)->count();

        $totalBillPrice    = (clone $query)->sum('bill_price');
        $totalFnbPrice     = (clone $query)->sum('fnb_price');

        // 9. Urutkan & Paginate 15 Data Per Halaman
        $transactions = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        // 10. Teks Periode Laporan
        $periodText = ($startDate === $endDate)
            ? Carbon::parse($startDate)->format('d/m/Y')
            : Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');

        return view('admin.reportadmin', compact(
            'transactions',
            'cashiers',
            'totalOmset',
            'totalCash',
            'totalNonCash',
            'totalTransactions',
            'totalBillPrice',
            'totalFnbPrice',
            'startDate',
            'endDate',
            'cashierId',
            'periodText'
        ));
    }
}
