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

        /*
         * 2. Logika Default Kasir:
         * - Jika user MEMILIH 'all' pada dropdown -> tampilkan semua kasir ($cashierId = null)
         * - Jika user MEMILIH kasir tertentu -> tampilkan ID kasir tersebut
         * - Jika BARU PERTAMA KALI BUKA HALAMAN (request 'cashier_id' tidak ada dalam URL)
         *   -> Default otomatis menggunakan ID user/kasir yang SEDANG LOGIN (auth()->id())
         */
        if ($request->has('cashier_id')) {
            $cashierId = $request->get('cashier_id') === 'all' ? null : $request->get('cashier_id');
        } else {
            $cashierId = auth()->id(); // Default ke akun yang sedang login (misal: Wok)
        }

        // 3. Base Query Transaksi Selesai (status: finished)
        $query = Transaction::where('status', 'finished')
            ->whereBetween('end_time', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);

        // 4. Filter berdasarkan Kasir Close (closed_by) jika $cashierId tidak null
        if (!empty($cashierId)) {
            $query->where('closed_by', $cashierId);
        }

        // 5. Ambil Daftar Semua Kasir untuk Dropdown
        $cashiers = User::orderBy('name', 'asc')->get();

        // 6. Hitung Total Ringkasan (Menggunakan clone $query agar akurat untuk seluruh rentang tanggal)
        $totalOmset        = (clone $query)->sum('grand_total');
        $totalCash         = (clone $query)->where('payment_method', 'cash')->sum('grand_total');
        $totalNonCash      = (clone $query)->where('payment_method', '!=', 'cash')->sum('grand_total');
        $totalTransactions = (clone $query)->count();

        // Tambahan breakdown Sewa Meja & FnB untuk Card Total Omset
        $totalBillPrice    = (clone $query)->sum('bill_price');
        $totalFnbPrice     = (clone $query)->sum('fnb_price');

        // 7. Urutkan & Paginate 15 Data Per Halaman
        $transactions = $query->orderBy('id', 'desc')->paginate(15);

        // 8. Teks Periode Laporan
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
