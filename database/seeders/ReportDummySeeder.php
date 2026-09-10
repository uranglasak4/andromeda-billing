<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * DummyTransactionSeeder
 * Data dummy 30 hari untuk laporan keuangan Andromeda Billiard & Cafe
 *
 * Aturan No Nota:
 *   FNB-DDMMYY-XXXX = FnB only (tanpa billing meja)
 *   BLM-DDMMYY-XXXX = Billing meja saja (tanpa FnB berbayar, boleh ada package yg include FnB gratis)
 *   ALL-DDMMYY-XXXX = Billing meja + FnB berbayar
 *
 * Aturan Pembayaran:
 *   cash     = pay_amount bisa lebih dari grand_total, ada kembalian
 *   qris     = pay_amount == grand_total, change_amount = 0
 *   transfer = pay_amount == grand_total, change_amount = 0
 *
 * User IDs: 1 = Wik (master), 2 = Wok (admin/kasir)
 * Pool table IDs: 1–16
 * Pricing rules: 1=Day Weekday(27k), 2=Night Weekday(38k), 3=Day Weekend(29k), 4=Night Weekend(43k)
 * Packages: 1=Galaxy Mix Combo(110k,240m), 2=Vitgo(50k,120m), 3=Nebulo(55k,120m),
 *           4=Kosmo(75k,120m), 5=Plater(85k,120m), 6=Auroram Combo(90k,120m),
 *           7=Auroram(90k,120m), 8=Noodle Orbit(95k,180m), 9=Chicken Orbit(115k,180m)
 *
 * FnB Products (sampel harga):
 *   1=Red Ladies(25k), 2=Americano(25k), 5=Caramel Latte(23k), 12=Green Tea Latte(25k),
 *   14=KSO/KSA(22k), 22=Es Teh(8k), 23=Air Mineral(5k), 24=Oat Milk(24k),
 *   28=Kopi Susu(22k), 29=Vit(7k), 30=Teh Botol(8k), 31=Pocari(40k),
 *   33=Rice Bowl(45k), 34=Mie Goreng(28k), 36=Roti Bakar(28k), 38=Pisang Goreng(23k),
 *   39=Cireng(18k), 40=Indomie(38k), 41=Nasi Goreng(29k)
 */
class ReportDummySeeder extends Seeder
{
    // Counter nota per hari (reset tiap hari)
    private int $notaCounter = 0;
    private string $currentDate = '';

    // ─── Helpers ───────────────────────────────────────────────────────────

    private function nextNota(string $prefix, Carbon $date): string
    {
        $dateStr = $date->format('dmy');
        if ($this->currentDate !== $dateStr) {
            $this->currentDate = $dateStr;
            $this->notaCounter = 0;
        }
        $this->notaCounter++;
        return sprintf('%s-%s-%04d', $prefix, $dateStr, $this->notaCounter);
    }

    /** Bulatkan ke atas kelipatan 500 */
    private function roundUp500(int $val): int
    {
        return (int) ceil($val / 500) * 500;
    }

    /** Hitung bill_price billing hourly berdasarkan rule & durasi (menit) */
    private function calcBill(int $pricePerHour, int $durationMin, int $minCharge = 10000): int
    {
        $calc = (int) round(($durationMin / 60) * $pricePerHour);
        return max($calc, $minCharge);
    }

    /** Pilih pay_amount yang wajar untuk cash (kelipatan 5000/10000/50000/100000) */
    private function cashRound(int $total): int
    {
        $options = [5000, 10000, 20000, 50000, 100000, 150000, 200000];
        foreach ($options as $step) {
            $rounded = (int) ceil($total / $step) * $step;
            if ($rounded >= $total)
                return $rounded;
        }
        return (int) ceil($total / 10000) * 10000;
    }

    /** Pilih metode pembayaran acak dengan bobot */
    private function randomMethod(): string
    {
        $r = rand(1, 10);
        if ($r <= 5)
            return 'cash';
        if ($r <= 8)
            return 'qris';
        return 'transfer';
    }

    /** Nama customer acak */
    private function randomName(): string
    {
        $names = [
            'ALDI',
            'BUDI',
            'CANDRA',
            'DANI',
            'ENDRA',
            'FAJAR',
            'GANI',
            'HENDRA',
            'IVAN',
            'JOKO',
            'KEVIN',
            'LUKI',
            'MARIO',
            'NANDO',
            'OKI',
            'PANDU',
            'REZA',
            'SANDI',
            'TONO',
            'UMI',
            'VINO',
            'WIDI',
            'YOGI',
            'ZAKI',
            'ANIS',
            'BELLA',
            'CICI',
            'DINDA',
            'ELSA',
            'FITRI',
            'GITA',
            'HANA',
            'INES',
            'JULIA',
            'KIKI',
            'LINA',
            'MONA',
            'NISA',
            'OKTA',
            'PUTRI',
            'RINA',
            'SARI',
            'TARI',
            'ULA',
            'VERA',
            'WULAN',
            'YENI',
            'ZARA',
            'RIZAL',
            'BAGAS',
            'CALEB',
            'DIMAS',
            'EVAN',
            'FARIS',
            'GALIH',
            'HARIS',
            'IRFAN',
            'JEFRI',
            'KAREL',
            'LANDO',
            'MAREL',
            'NABIL',
        ];
        return $names[array_rand($names)];
    }

    /** FnB items acak beserta total harganya */
    private function randomFnbItems(int $transactionId, string $customerName, Carbon $ts): array
    {
        $menu = [
            ['id' => 1, 'name' => 'Red Ladies', 'price' => 25000],
            ['id' => 2, 'name' => 'Americano', 'price' => 25000],
            ['id' => 5, 'name' => 'Caramel Latte', 'price' => 23000],
            ['id' => 12, 'name' => 'Green Tea Latte', 'price' => 25000],
            ['id' => 14, 'name' => 'KSO/KSA', 'price' => 22000],
            ['id' => 22, 'name' => 'Es Teh', 'price' => 8000],
            ['id' => 24, 'name' => 'Oat Milk', 'price' => 24000],
            ['id' => 28, 'name' => 'Kopi Susu', 'price' => 22000],
            ['id' => 34, 'name' => 'Mie Goreng', 'price' => 28000],
            ['id' => 36, 'name' => 'Roti Bakar', 'price' => 28000],
            ['id' => 38, 'name' => 'Pisang Goreng', 'price' => 23000],
            ['id' => 39, 'name' => 'Cireng', 'price' => 18000],
            ['id' => 40, 'name' => 'Indomie', 'price' => 38000],
            ['id' => 41, 'name' => 'Nasi Goreng', 'price' => 29000],
        ];

        shuffle($menu);
        $count = rand(1, 3);
        $selected = array_slice($menu, 0, $count);
        $rows = [];
        $total = 0;

        foreach ($selected as $item) {
            $qty = rand(1, 2);
            $sub = $item['price'] * $qty;
            $total += $sub;
            $rows[] = [
                'transaction_id' => $transactionId,
                'fnb_product_id' => $item['id'],
                'customer_name' => $customerName,
                'stock' => $qty,
                'price' => $item['price'],
                'subtotal' => $sub,
                'payment_status' => 'paid',
                'created_at' => $ts,
                'updated_at' => $ts,
            ];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    // ─── Main run ──────────────────────────────────────────────────────────

    public function run(): void
    {
        // Kosongkan dulu (urutan penting karena FK)
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('order_fnbs')->truncate();
        DB::table('transactions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Seeding dummy transactions (30 hari)...');

        // Kasir utama (user id 2 = Wok admin)
        $kasirId = 2;

        // Iterasi 30 hari ke belakang
        for ($day = 29; $day >= 0; $day--) {
            $date = Carbon::now()->subDays($day)->startOfDay();
            $isWeekend = in_array($date->isoweekday(), [5, 6, 7]); // Jum, Sab, Min

            // Pilih rule harga berdasarkan hari
            $dayRuleId = $isWeekend ? 3 : 1; // Day Weekend=29k, Day Weekday=27k
            $nightRuleId = $isWeekend ? 4 : 2; // Night Weekend=43k, Night Weekday=38k
            $dayPrice = $isWeekend ? 29000 : 27000;
            $nightPrice = $isWeekend ? 43000 : 38000;

            // Jumlah transaksi per hari: 8-20 di weekday, 15-30 di weekend
            $txCount = $isWeekend ? rand(15, 25) : rand(8, 16);

            // Nomor meja yang sudah dipakai hari ini (hindari duplikat meja aktif bersamaan)
            $usedTables = [];

            for ($t = 0; $t < $txCount; $t++) {

                // ── Pilih skenario transaksi ──────────────────────────────
                $scenario = rand(1, 10);

                // 20% FNB Standalone
                if ($scenario <= 2) {
                    $this->seedFnbStandalone($date, $kasirId);
                    continue;
                }

                // Pilih meja yang belum dipakai hari ini
                $availableTables = array_diff(range(1, 14), $usedTables);
                if (empty($availableTables)) {
                    // Semua meja sudah terpakai — tambah FNB standalone saja
                    $this->seedFnbStandalone($date, $kasirId);
                    continue;
                }
                $reindexed = array_values($availableTables);
                $tableNum = $reindexed[array_rand($reindexed)];
                $usedTables[] = $tableNum;
                $tableId = $tableNum; // id sama dengan table_number di seeder awal

                // ── Jam mulai ────────────────────────────────────────────
                // Sesi siang: 11:00–17:30, sesi malam: 18:30–01:00
                $isSiang = rand(0, 1) === 1;
                if ($isSiang) {
                    $startHour = rand(11, 17);
                    $startMin = rand(0, 59);
                } else {
                    $startHour = rand(18, 23);
                    $startMin = rand(0, 59);
                }
                $startTime = $date->copy()->setHour($startHour)->setMinute($startMin)->setSecond(rand(0, 59));

                $ruleId = $isSiang ? $dayRuleId : $nightRuleId;
                $priceHour = $isSiang ? $dayPrice : $nightPrice;

                // ── Pilih tipe billing ────────────────────────────────────
                // 40% hourly, 30% package, 30% personal
                $billingRand = rand(1, 10);

                if ($billingRand <= 4) {
                    // HOURLY
                    $this->seedHourly(
                        $date,
                        $startTime,
                        $tableId,
                        $tableNum,
                        $ruleId,
                        $priceHour,
                        $kasirId,
                        $scenario
                    );
                } elseif ($billingRand <= 7) {
                    // PACKAGE (hanya weekday — package Andromeda khusus weekday)
                    if ($isWeekend) {
                        // Weekend tidak ada package → fallback ke hourly
                        $this->seedHourly(
                            $date,
                            $startTime,
                            $tableId,
                            $tableNum,
                            $ruleId,
                            $priceHour,
                            $kasirId,
                            $scenario
                        );
                    } else {
                        $this->seedPackage($date, $startTime, $tableId, $tableNum, $kasirId, $scenario);
                    }
                } else {
                    // PERSONAL
                    $this->seedPersonal(
                        $date,
                        $startTime,
                        $tableId,
                        $tableNum,
                        $ruleId,
                        $priceHour,
                        $kasirId,
                        $scenario
                    );
                }
            }

            $this->command->line("  ✓ {$date->format('d M Y')} — {$txCount} transaksi");
        }

        $totalTx = DB::table('transactions')->count();
        $totalFnb = DB::table('order_fnbs')->count();
        $this->command->info("Selesai! Total: {$totalTx} transaksi, {$totalFnb} order FnB.");
    }

    // ─── Seeder per Tipe ───────────────────────────────────────────────────

    /** FnB Standalone (tanpa meja biliar) */
    private function seedFnbStandalone(Carbon $date, int $kasirId): void
    {
        $customerName = strtoupper($this->randomName());
        $startTs = $date->copy()->setHour(rand(11, 22))->setMinute(rand(0, 59))->setSecond(rand(0, 59));

        // FNB standalone: pool_table_id = NULL, billing_type = personal (dipakai untuk FnB-only)
        $txId = DB::table('transactions')->insertGetId([
            'created_by' => $kasirId,
            'closed_by' => $kasirId,
            'pool_table_id' => null,
            'customer_name' => $customerName,
            'billing_type' => 'personal',
            'pricing_rule_id' => null,
            'package_id' => null,
            'start_time' => $startTs,
            'end_time' => $startTs,
            'duration' => 0,
            'bill_price' => 0,
            'fnb_price' => 0, // diisi setelah FnB
            'grand_total' => 0,
            'payment_method' => 'cash',
            'pay_amount' => 0,
            'change_amount' => 0,
            'status' => 'finished',
            'created_at' => $startTs,
            'updated_at' => $startTs,
        ]);

        $fnb = $this->randomFnbItems($txId, $customerName, $startTs);
        if (!empty($fnb['rows'])) {
            DB::table('order_fnbs')->insert($fnb['rows']);
        }

        $fnbTotal = $fnb['total'];
        $grandTotal = $fnbTotal;
        $method = $this->randomMethod();
        $payAmount = ($method === 'cash') ? $this->cashRound($grandTotal) : $grandTotal;
        $change = $payAmount - $grandTotal;

        DB::table('transactions')->where('id', $txId)->update([
            'fnb_price' => $fnbTotal,
            'grand_total' => $grandTotal,
            'payment_method' => $method,
            'pay_amount' => $payAmount,
            'change_amount' => $change,
        ]);
    }

    /** Billing Hourly */
    private function seedHourly(
        Carbon $date,
        Carbon $startTime,
        int $tableId,
        int $tableNum,
        int $ruleId,
        int $priceHour,
        int $kasirId,
        int $scenario
    ): void {
        $customerName = strtoupper($this->randomName());
        $durationMin = rand(1, 4) * 60; // 1–4 jam
        $endTime = $startTime->copy()->addMinutes($durationMin);
        $billPrice = $this->calcBill($priceHour, $durationMin);

        // Apakah ada FnB? (40% kemungkinan → ALL, 60% → BLM)
        $hasFnb = ($scenario > 6);
        $fnbTotal = 0;
        $fnbRows = [];

        if ($hasFnb) {
            $fnb = $this->randomFnbItems(0, $customerName, $endTime);
            $fnbTotal = $fnb['total'];
            $fnbRows = $fnb['rows'];
        }

        $grandTotal = $billPrice + $fnbTotal;
        $method = $this->randomMethod();
        $payAmount = ($method === 'cash') ? $this->cashRound($grandTotal) : $grandTotal;
        $change = $payAmount - $grandTotal;

        $txId = DB::table('transactions')->insertGetId([
            'created_by' => $kasirId,
            'closed_by' => $kasirId,
            'pool_table_id' => $tableId,
            'customer_name' => $customerName,
            'billing_type' => 'hourly',
            'pricing_rule_id' => $ruleId,
            'package_id' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $durationMin,
            'bill_price' => $billPrice,
            'fnb_price' => $fnbTotal,
            'grand_total' => $grandTotal,
            'payment_method' => $method,
            'pay_amount' => $payAmount,
            'change_amount' => $change,
            'status' => 'finished',
            'created_at' => $startTime,
            'updated_at' => $endTime,
        ]);

        if ($hasFnb && !empty($fnbRows)) {
            foreach ($fnbRows as &$row) {
                $row['transaction_id'] = $txId;
            }
            DB::table('order_fnbs')->insert($fnbRows);
        }
    }

    /** Billing Package */
    private function seedPackage(
        Carbon $date,
        Carbon $startTime,
        int $tableId,
        int $tableNum,
        int $kasirId,
        int $scenario
    ): void {
        $customerName = strtoupper($this->randomName());

        // Pilih package weekday (id 2–9)
        $packages = [
            ['id' => 2, 'price' => 50000, 'duration' => 120, 'name' => 'Vitgo'],
            ['id' => 3, 'price' => 55000, 'duration' => 120, 'name' => 'Nebulo'],
            ['id' => 4, 'price' => 75000, 'duration' => 120, 'name' => 'Kosmo'],
            ['id' => 5, 'price' => 85000, 'duration' => 120, 'name' => 'Plater'],
            ['id' => 6, 'price' => 90000, 'duration' => 120, 'name' => 'Auroram Combo'],
            ['id' => 7, 'price' => 90000, 'duration' => 120, 'name' => 'Auroram'],
            ['id' => 8, 'price' => 95000, 'duration' => 180, 'name' => 'Noodle Orbit'],
            ['id' => 9, 'price' => 115000, 'duration' => 180, 'name' => 'Chicken Orbit'],
        ];
        $pkg = $packages[array_rand($packages)];
        $endTime = $startTime->copy()->addMinutes($pkg['duration']);
        $billPrice = $pkg['price'];

        // Apakah tambah FnB berbayar? (35% kemungkinan → ALL, 65% → BLM)
        $hasFnb = ($scenario > 7);
        $fnbTotal = 0;
        $fnbRows = [];

        if ($hasFnb) {
            $fnb = $this->randomFnbItems(0, $customerName, $endTime);
            $fnbTotal = $fnb['total'];
            $fnbRows = $fnb['rows'];
        }

        $grandTotal = $billPrice + $fnbTotal;
        $method = $this->randomMethod();
        $payAmount = ($method === 'cash') ? $this->cashRound($grandTotal) : $grandTotal;
        $change = $payAmount - $grandTotal;

        $txId = DB::table('transactions')->insertGetId([
            'created_by' => $kasirId,
            'closed_by' => $kasirId,
            'pool_table_id' => $tableId,
            'customer_name' => $customerName,
            'billing_type' => 'package',
            'pricing_rule_id' => null,
            'package_id' => $pkg['id'],
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $pkg['duration'],
            'bill_price' => $billPrice,
            'fnb_price' => $fnbTotal,
            'grand_total' => $grandTotal,
            'payment_method' => $method,
            'pay_amount' => $payAmount,
            'change_amount' => $change,
            'status' => 'finished',
            'created_at' => $startTime,
            'updated_at' => $endTime,
        ]);

        if ($hasFnb && !empty($fnbRows)) {
            foreach ($fnbRows as &$row) {
                $row['transaction_id'] = $txId;
            }
            DB::table('order_fnbs')->insert($fnbRows);
        }
    }

    /** Billing Personal (open time — stopwatch) */
    private function seedPersonal(
        Carbon $date,
        Carbon $startTime,
        int $tableId,
        int $tableNum,
        int $ruleId,
        int $priceHour,
        int $kasirId,
        int $scenario
    ): void {
        $customerName = strtoupper($this->randomName());
        $durationMin = rand(20, 180); // 20 menit – 3 jam
        $endTime = $startTime->copy()->addMinutes($durationMin);

        // Hitung bill personal (per menit × durasi, min charge)
        $pricePerMin = $priceHour / 60;
        $calculated = (int) round($pricePerMin * $durationMin);
        $billPrice = max($calculated, 10000);

        // Apakah ada FnB? (45% kemungkinan)
        $hasFnb = ($scenario > 5);
        $fnbTotal = 0;
        $fnbRows = [];

        if ($hasFnb) {
            $fnb = $this->randomFnbItems(0, $customerName, $endTime);
            $fnbTotal = $fnb['total'];
            $fnbRows = $fnb['rows'];
        }

        $grandTotal = $billPrice + $fnbTotal;
        $method = $this->randomMethod();
        $payAmount = ($method === 'cash') ? $this->cashRound($grandTotal) : $grandTotal;
        $change = $payAmount - $grandTotal;

        $txId = DB::table('transactions')->insertGetId([
            'created_by' => $kasirId,
            'closed_by' => $kasirId,
            'pool_table_id' => $tableId,
            'customer_name' => $customerName,
            'billing_type' => 'personal',
            'pricing_rule_id' => $ruleId,
            'package_id' => null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $durationMin,
            'bill_price' => $billPrice,
            'fnb_price' => $fnbTotal,
            'grand_total' => $grandTotal,
            'payment_method' => $method,
            'pay_amount' => $payAmount,
            'change_amount' => $change,
            'status' => 'finished',
            'created_at' => $startTime,
            'updated_at' => $endTime,
        ]);

        if ($hasFnb && !empty($fnbRows)) {
            foreach ($fnbRows as &$row) {
                $row['transaction_id'] = $txId;
            }
            DB::table('order_fnbs')->insert($fnbRows);
        }
    }
}
