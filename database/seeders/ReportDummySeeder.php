<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportDummySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil ID Kasir 'Ted' (atau user pertama)
        $userId = DB::table('users')->value('id') ?? 1;

        // 2. Ambil ID Meja billiard SAJA (Mengabaikan ID 16 / Standalone)
        $tableIds = DB::table('pool_tables')
            ->where('id', '!=', 16) // Hilangkan ID Standalone dari daftar acak meja
            ->pluck('id')
            ->toArray();

        if (empty($tableIds)) {
            $tableIds = [1, 2, 3, 4, 5];
        }

        $packageIds = DB::table('packages')->pluck('id')->toArray();
        $pricingRuleIds = DB::table('pricing_rules')->pluck('id')->toArray();

        $billingTypes = ['hourly', 'personal', 'package'];
        $paymentMethods = ['cash', 'qris', 'transfer'];
        $customerNames = ['Budi', 'Andi', 'Rian', 'Fajri', 'Deni', 'Siti', 'Bagus', 'Eko'];

        // Clean up tabel transactions
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('transactions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Generate 15 data transaksi meja billiard selesai untuk HARI INI
        for ($i = 1; $i <= 15; $i++) {
            $type = $billingTypes[array_rand($billingTypes)];
            $method = $paymentMethods[array_rand($paymentMethods)];
            $customer = $customerNames[array_rand($customerNames)];
            $tableId = $tableIds[array_rand($tableIds)];

            // Simulasi waktu bermain hari ini (jam 10 pagi - 8 malam)
            $start = Carbon::today()->setHour(rand(10, 19))->setMinute(rand(0, 59));
            $durationMinutes = rand(30, 180);
            $end = (clone $start)->addMinutes($durationMinutes);

            // Calculation Keuangan Dummy
            $sewaMeja = rand(3, 12) * 10000;
            $hargaFnb = rand(0, 4) * 15000;
            $grandTotal = $sewaMeja + $hargaFnb;

            $bayar = ceil($grandTotal / 50000) * 50000;
            if ($bayar < $grandTotal) {
                $bayar = $grandTotal + 10000;
            }
            if ($method !== 'cash') {
                $bayar = $grandTotal;
            }
            $kembali = $bayar - $grandTotal;

            DB::table('transactions')->insert([
                'created_by'      => $userId,
                'closed_by'       => $userId,
                'pool_table_id'   => $tableId, // Hanya memakai ID meja murni (bukan 16)
                'customer_name'   => $customer,
                'billing_type'    => $type,
                'pricing_rule_id' => !empty($pricingRuleIds) ? $pricingRuleIds[array_rand($pricingRuleIds)] : null,
                'package_id'      => ($type === 'package' && !empty($packageIds)) ? $packageIds[array_rand($packageIds)] : null,
                'start_time'      => $start,
                'end_time'        => $end,
                'duration'        => $durationMinutes,
                'bill_price'      => $sewaMeja,
                'fnb_price'       => $hargaFnb,
                'grand_total'     => $grandTotal,
                'payment_method'  => $method,
                'pay_amount'      => $bayar,
                'change_amount'   => $kembali,
                'status'          => 'finished',
                'created_at'      => $end,
                'updated_at'      => $end,
            ]);
        }
    }
}
