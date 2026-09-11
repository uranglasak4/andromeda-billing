<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * ReportDummySeeder — Data dummy 30 hari laporan keuangan Andromeda Billiard & Cafe
 *
 * ATURAN NO NOTA:
 *   FNB-DDMMYY-XXXX = FnB only (walk-in order tanpa billing meja)
 *   BLM-DDMMYY-XXXX = Billing meja saja (tanpa FnB berbayar)
 *   ALL-DDMMYY-XXXX = Billing meja + FnB berbayar
 *
 * ATURAN PEMBAYARAN:
 *   cash     = pay_amount >= grand_total, ada kembalian
 *   qris     = pay_amount == grand_total, change = 0
 *   transfer = pay_amount == grand_total, change = 0
 *
 * USER (KASIR ADMIN SAJA):
 *   id=4 Wok — Shift Day  (buka billing 11:00–17:59, created_by=4)
 *   id=5 Ted — Shift Night (buka billing 18:00–02:59, created_by=5)
 *   closed_by = kasir yang aktif saat billing selesai
 *     → jika end_time < 18:00 → Wok (4)
 *     → jika end_time >= 18:00 atau < 03:00 → Ted (5)
 *
 * JAM OPERASIONAL: 11:00–03:00 dini hari
 *
 * PRICING RULES:
 *   1=Day Weekday(27k/jam), 2=Night Weekday(38k/jam)
 *   3=Day Weekend(29k/jam), 4=Night Weekend(43k/jam)
 *
 * PACKAGES (weekday only):
 *   2=Vitgo(50k,120m), 3=Nebulo(55k,120m), 4=Kosmo(75k,120m)
 *   5=Plater(85k,120m), 6=Auroram Combo(90k,120m), 7=Auroram(90k,120m)
 *   8=Noodle Orbit(95k,180m), 9=Chicken Orbit(115k,180m)
 */
class ReportDummySeeder extends Seeder
{
    private int    $notaCounter  = 0;
    private string $currentDate  = '';

    // User ID kasir
    private const WOK = 4; // Shift Day  11:00–17:59
    private const TED = 5; // Shift Night 18:00–02:59

    // ══════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════

    /** Generate nomor nota berurutan per hari */
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

    /**
     * Tentukan kasir berdasarkan jam
     * Shift Day  (Wok): 11:00–17:59
     * Shift Night (Ted): 18:00–02:59
     */
    private function kasirByTime(Carbon $time): int
    {
        $h = (int) $time->format('H');
        // Dini hari 00:00–02:59 masih shift night Ted
        if ($h >= 18 || $h < 3) return self::TED;
        return self::WOK;
    }

    /** Bulatkan cash ke nominal wajar (kelipatan 500) */
    private function cashRound(int $total): int
    {
        // Pembulatan ke atas ke nominal umum
        $steps = [500, 1000, 2000, 5000, 10000, 20000, 50000, 100000];
        foreach ($steps as $step) {
            $rounded = (int) ceil($total / $step) * $step;
            if ($rounded >= $total && ($rounded - $total) <= 10000) {
                return $rounded;
            }
        }
        return (int) ceil($total / 5000) * 5000;
    }

    /** Hitung bill hourly/personal */
    private function calcBill(int $pricePerHour, int $durationMin, int $minCharge = 10000): int
    {
        $calc = (int) round(($durationMin / 60) * $pricePerHour);
        return max($calc, $minCharge);
    }

    /** Pilih metode pembayaran dengan bobot realistis */
    private function randomMethod(): string
    {
        $r = rand(1, 10);
        if ($r <= 5) return 'cash';     // 50%
        if ($r <= 8) return 'qris';     // 30%
        return 'transfer';              // 20%
    }

    /** Nama customer acak */
    private function randomName(): string
    {
        $names = [
            'ALDI','BUDI','CANDRA','DANI','ENDRA','FAJAR','GANI','HENDRA',
            'IVAN','JOKO','KEVIN','LUKI','MARIO','NANDO','OKI','PANDU',
            'REZA','SANDI','TONO','VINO','WIDI','YOGI','ZAKI','RIZAL',
            'BAGAS','DIMAS','EVAN','FARIS','GALIH','HARIS','IRFAN','JEFRI',
            'KAREL','LANDO','MAREL','NABIL','OSCAR','PETRA','QORI','RAFI',
            'ANIS','BELLA','CICI','DINDA','ELSA','FITRI','GITA','HANA',
            'INES','JULIA','KIKI','LINA','MONA','NISA','PUTRI','RINA',
            'SARI','TARI','VERA','WULAN','YENI','ZARA','AULIA','BUNGA',
        ];
        return $names[array_rand($names)];
    }

    /**
     * Determine pricing rule dan harga berdasarkan jam & hari
     * Return: [rule_id, price_per_hour]
     */
    private function getPricingRule(Carbon $time, bool $isWeekend): array
    {
        $h = (int) $time->format('H');
        $isDay = ($h >= 11 && $h < 18);

        if ($isWeekend) {
            return $isDay ? [3, 29000] : [4, 43000];
        } else {
            return $isDay ? [1, 27000] : [2, 38000];
        }
    }

    /**
     * Generate FnB order items
     * Return: ['rows' => [...], 'total' => int]
     */
    private function randomFnbItems(int $txId, string $custName, Carbon $ts): array
    {
        $menu = [
            ['id'=>1,  'price'=>25000], // Red Ladies
            ['id'=>2,  'price'=>25000], // Americano
            ['id'=>5,  'price'=>23000], // Caramel Latte
            ['id'=>12, 'price'=>25000], // Green Tea Latte
            ['id'=>14, 'price'=>22000], // KSO/KSA
            ['id'=>22, 'price'=>8000],  // Es Teh
            ['id'=>24, 'price'=>24000], // Oat Milk
            ['id'=>28, 'price'=>22000], // Kopi Susu
            ['id'=>34, 'price'=>28000], // Mie Goreng
            ['id'=>36, 'price'=>28000], // Roti Bakar
            ['id'=>38, 'price'=>23000], // Pisang Goreng
            ['id'=>39, 'price'=>18000], // Cireng
            ['id'=>40, 'price'=>38000], // Indomie
            ['id'=>41, 'price'=>29000], // Nasi Goreng
        ];

        shuffle($menu);
        $count    = rand(1, 3);
        $selected = array_slice($menu, 0, $count);
        $rows     = [];
        $total    = 0;

        foreach ($selected as $item) {
            $qty   = rand(1, 2);
            $sub   = $item['price'] * $qty;
            $total += $sub;
            $rows[] = [
                'transaction_id' => $txId,
                'fnb_product_id' => $item['id'],
                'customer_name'  => $custName,
                'stock'          => $qty,
                'price'          => $item['price'],
                'subtotal'       => $sub,
                'payment_status' => 'paid',
                'created_at'     => $ts,
                'updated_at'     => $ts,
            ];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /** Finalize & insert transaksi + FnB, return transaction id */
    private function insertTransaction(array $tx, array $fnbRows): int
    {
        $txId = DB::table('transactions')->insertGetId($tx);
        if (!empty($fnbRows)) {
            foreach ($fnbRows as &$row) {
                $row['transaction_id'] = $txId;
            }
            DB::table('order_fnbs')->insert($fnbRows);
        }
        return $txId;
    }

    // ══════════════════════════════════════════════════════════════
    //  SEEDER TYPES
    // ══════════════════════════════════════════════════════════════

    /** FnB walk-in standalone (tanpa meja biliar) */
    private function seedFnbStandalone(Carbon $date): void
    {
        $custName = strtoupper($this->randomName());
        // FnB standalone jam 11:00–22:00
        $h       = rand(11, 22);
        $ts      = $date->copy()->setHour($h)->setMinute(rand(0, 59))->setSecond(rand(0, 59));
        $kasir   = $this->kasirByTime($ts);

        // Insert dulu dengan grand_total=0, update setelah dapat FnB total
        $txId = DB::table('transactions')->insertGetId([
            'created_by'      => $kasir,
            'closed_by'       => $kasir,
            'pool_table_id'   => null,
            'customer_name'   => $custName,
            'billing_type'    => 'personal',
            'pricing_rule_id' => null,
            'package_id'      => null,
            'start_time'      => $ts,
            'end_time'        => $ts,
            'duration'        => 0,
            'bill_price'      => 0,
            'fnb_price'       => 0,
            'grand_total'     => 0,
            'payment_method'  => 'cash',
            'pay_amount'      => 0,
            'change_amount'   => 0,
            'status'          => 'finished',
            'created_at'      => $ts,
            'updated_at'      => $ts,
        ]);

        $fnb        = $this->randomFnbItems($txId, $custName, $ts);
        $grandTotal = $fnb['total'];
        $method     = $this->randomMethod();
        $payAmount  = ($method === 'cash') ? $this->cashRound($grandTotal) : $grandTotal;
        $change     = $payAmount - $grandTotal;

        DB::table('order_fnbs')->insert($fnb['rows']);
        DB::table('transactions')->where('id', $txId)->update([
            'fnb_price'      => $grandTotal,
            'grand_total'    => $grandTotal,
            'payment_method' => $method,
            'pay_amount'     => $payAmount,
            'change_amount'  => $change,
        ]);
    }

    /** Billing Hourly (BLM atau ALL) */
    private function seedHourly(Carbon $startTime, int $tableId, bool $isWeekend, bool $hasFnb): void
    {
        $custName = strtoupper($this->randomName());
        $durMin   = rand(1, 4) * 60; // 1–4 jam
        $endTime  = $startTime->copy()->addMinutes($durMin);

        [$ruleId, $priceHour] = $this->getPricingRule($startTime, $isWeekend);
        $billPrice = $this->calcBill($priceHour, $durMin);

        $createdBy = $this->kasirByTime($startTime);
        $closedBy  = $this->kasirByTime($endTime);

        $fnbTotal = 0;
        $fnbRows  = [];
        if ($hasFnb) {
            $fnb      = $this->randomFnbItems(0, $custName, $endTime);
            $fnbTotal = $fnb['total'];
            $fnbRows  = $fnb['rows'];
        }

        $grandTotal = $billPrice + $fnbTotal;
        $method     = $this->randomMethod();
        $payAmount  = ($method === 'cash') ? $this->cashRound($grandTotal) : $grandTotal;
        $change     = $payAmount - $grandTotal;

        $this->insertTransaction([
            'created_by'      => $createdBy,
            'closed_by'       => $closedBy,
            'pool_table_id'   => $tableId,
            'customer_name'   => $custName,
            'billing_type'    => 'hourly',
            'pricing_rule_id' => $ruleId,
            'package_id'      => null,
            'start_time'      => $startTime,
            'end_time'        => $endTime,
            'duration'        => $durMin,
            'bill_price'      => $billPrice,
            'fnb_price'       => $fnbTotal,
            'grand_total'     => $grandTotal,
            'payment_method'  => $method,
            'pay_amount'      => $payAmount,
            'change_amount'   => $change,
            'status'          => 'finished',
            'created_at'      => $startTime,
            'updated_at'      => $endTime,
        ], $fnbRows);
    }

    /** Billing Package (BLM atau ALL) — weekday only */
    private function seedPackage(Carbon $startTime, int $tableId, bool $hasFnb): void
    {
        $custName = strtoupper($this->randomName());

        $packages = [
            ['id'=>2, 'price'=>50000,  'dur'=>120],
            ['id'=>3, 'price'=>55000,  'dur'=>120],
            ['id'=>4, 'price'=>75000,  'dur'=>120],
            ['id'=>5, 'price'=>85000,  'dur'=>120],
            ['id'=>6, 'price'=>90000,  'dur'=>120],
            ['id'=>7, 'price'=>90000,  'dur'=>120],
            ['id'=>8, 'price'=>95000,  'dur'=>180],
            ['id'=>9, 'price'=>115000, 'dur'=>180],
        ];
        $pkg      = $packages[array_rand($packages)];
        $endTime  = $startTime->copy()->addMinutes($pkg['dur']);
        $billPrice = $pkg['price'];

        $createdBy = $this->kasirByTime($startTime);
        $closedBy  = $this->kasirByTime($endTime);

        $fnbTotal = 0;
        $fnbRows  = [];
        if ($hasFnb) {
            $fnb      = $this->randomFnbItems(0, $custName, $endTime);
            $fnbTotal = $fnb['total'];
            $fnbRows  = $fnb['rows'];
        }

        $grandTotal = $billPrice + $fnbTotal;
        $method     = $this->randomMethod();
        $payAmount  = ($method === 'cash') ? $this->cashRound($grandTotal) : $grandTotal;
        $change     = $payAmount - $grandTotal;

        $this->insertTransaction([
            'created_by'      => $createdBy,
            'closed_by'       => $closedBy,
            'pool_table_id'   => $tableId,
            'customer_name'   => $custName,
            'billing_type'    => 'package',
            'pricing_rule_id' => null,
            'package_id'      => $pkg['id'],
            'start_time'      => $startTime,
            'end_time'        => $endTime,
            'duration'        => $pkg['dur'],
            'bill_price'      => $billPrice,
            'fnb_price'       => $fnbTotal,
            'grand_total'     => $grandTotal,
            'payment_method'  => $method,
            'pay_amount'      => $payAmount,
            'change_amount'   => $change,
            'status'          => 'finished',
            'created_at'      => $startTime,
            'updated_at'      => $endTime,
        ], $fnbRows);
    }

    /** Billing Personal open-time (BLM atau ALL) */
    private function seedPersonal(Carbon $startTime, int $tableId, bool $isWeekend, bool $hasFnb): void
    {
        $custName = strtoupper($this->randomName());
        $durMin   = rand(15, 200); // 15 menit – 3 jam 20 menit
        $endTime  = $startTime->copy()->addMinutes($durMin);

        [$ruleId, $priceHour] = $this->getPricingRule($startTime, $isWeekend);

        // Hitung harga personal per-menit dengan segmen tarif
        // (simplified: pakai tarif start_time saja untuk seeder)
        $pricePerMin = $priceHour / 60;
        $calculated  = (int) round($pricePerMin * $durMin);
        $billPrice   = max($calculated, 10000);

        $createdBy = $this->kasirByTime($startTime);
        $closedBy  = $this->kasirByTime($endTime);

        $fnbTotal = 0;
        $fnbRows  = [];
        if ($hasFnb) {
            $fnb      = $this->randomFnbItems(0, $custName, $endTime);
            $fnbTotal = $fnb['total'];
            $fnbRows  = $fnb['rows'];
        }

        $grandTotal = $billPrice + $fnbTotal;
        $method     = $this->randomMethod();
        $payAmount  = ($method === 'cash') ? $this->cashRound($grandTotal) : $grandTotal;
        $change     = $payAmount - $grandTotal;

        $this->insertTransaction([
            'created_by'      => $createdBy,
            'closed_by'       => $closedBy,
            'pool_table_id'   => $tableId,
            'customer_name'   => $custName,
            'billing_type'    => 'personal',
            'pricing_rule_id' => $ruleId,
            'package_id'      => null,
            'start_time'      => $startTime,
            'end_time'        => $endTime,
            'duration'        => $durMin,
            'bill_price'      => $billPrice,
            'fnb_price'       => $fnbTotal,
            'grand_total'     => $grandTotal,
            'payment_method'  => $method,
            'pay_amount'      => $payAmount,
            'change_amount'   => $change,
            'status'          => 'finished',
            'created_at'      => $startTime,
            'updated_at'      => $endTime,
        ], $fnbRows);
    }

    // ══════════════════════════════════════════════════════════════
    //  MAIN RUN
    // ══════════════════════════════════════════════════════════════

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('order_fnbs')->truncate();
        DB::table('transactions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Seeding dummy transactions (30 hari)...');

        for ($day = 29; $day >= 0; $day--) {
            $date      = Carbon::now()->subDays($day)->startOfDay();
            $isWeekend = in_array($date->isoweekday(), [5, 6, 7]); // Jumat, Sabtu, Minggu

            // Volume harian: lebih ramai di weekend & hari-hari tertentu
            $txBillingCount = $isWeekend ? rand(12, 20) : rand(7, 14);
            $txFnbCount     = rand(2, 6); // FnB standalone walk-in

            // ── FnB Standalone ──────────────────────────────────────────
            for ($f = 0; $f < $txFnbCount; $f++) {
                $this->seedFnbStandalone($date);
            }

            // ── Billing Meja ─────────────────────────────────────────────
            // Pilih meja acak tanpa duplikat dalam 1 "gelombang" waktu
            // Simulasikan 2 gelombang: siang (11:00–17:00) dan malam (18:00–02:00)
            $tablePool = range(1, 14); // Meja 1–14 yang aktif
            shuffle($tablePool);

            $sessionsPerDay = min($txBillingCount, count($tablePool));
            $usedInWave     = [];

            for ($s = 0; $s < $sessionsPerDay; $s++) {

                // Pilih meja: prioritas yang belum dipakai, tapi boleh repeat setelah satu sesi selesai
                $available = array_diff($tablePool, $usedInWave);
                if (empty($available)) {
                    // Reset: gelombang baru (meja boleh dipakai lagi)
                    $usedInWave = [];
                    $available  = $tablePool;
                }

                // ✅ FIX: reindex sebelum array_rand
                $available = array_values($available);
                $tableId   = $available[array_rand($available)];
                $usedInWave[] = $tableId;

                // ── Tentukan jam mulai ────────────────────────────────────
                // Operasional 11:00–02:59, distribusi ke siang dan malam
                $isSiangSession = rand(0, 10) <= 6; // 60% siang, 40% malam
                if ($isSiangSession) {
                    $startHour = rand(11, 17);
                } else {
                    // Malam: 18:00–01:00
                    $startHour = rand(18, 25) % 24;
                }
                $startMin  = rand(0, 59);
                $startSec  = rand(0, 59);
                $startTime = $date->copy()->setHour($startHour)->setMinute($startMin)->setSecond($startSec);

                // Jika jam > 24 (dini hari), shift ke hari berikutnya
                if ($startHour >= 24) {
                    $startTime = $date->copy()->addDay()->setHour($startHour - 24)->setMinute($startMin)->setSecond($startSec);
                }

                // ── Tentukan tipe billing ─────────────────────────────────
                // Weekday: 35% hourly, 35% package, 30% personal
                // Weekend: 40% hourly, 10% package(skip→hourly), 50% personal
                $billingRand = rand(1, 10);
                $hasFnb      = rand(1, 10) <= 4; // 40% ada tambahan FnB

                if ($isWeekend) {
                    if ($billingRand <= 4) {
                        $this->seedHourly($startTime, $tableId, $isWeekend, $hasFnb);
                    } else {
                        $this->seedPersonal($startTime, $tableId, $isWeekend, $hasFnb);
                    }
                } else {
                    if ($billingRand <= 3) {
                        $this->seedHourly($startTime, $tableId, $isWeekend, $hasFnb);
                    } elseif ($billingRand <= 6) {
                        // Package hanya tersedia jam 11:00–17:00 weekday
                        $ph = (int) $startTime->format('H');
                        if ($ph >= 11 && $ph < 17) {
                            $this->seedPackage($startTime, $tableId, $hasFnb);
                        } else {
                            $this->seedHourly($startTime, $tableId, $isWeekend, $hasFnb);
                        }
                    } else {
                        $this->seedPersonal($startTime, $tableId, $isWeekend, $hasFnb);
                    }
                }
            }

            $totalHariIni = DB::table('transactions')
                ->whereDate('created_at', $date->format('Y-m-d'))
                ->count();
            $this->command->line("  ✓ {$date->format('d M Y')} " .
                ($isWeekend ? '(Weekend)' : '(Weekday)') .
                " — {$totalHariIni} transaksi");
        }

        $totalTx  = DB::table('transactions')->count();
        $totalFnb = DB::table('order_fnbs')->count();
        $this->command->info("✅ Selesai! Total: {$totalTx} transaksi, {$totalFnb} order FnB.");
    }
}
