<?php

namespace Database\Seeders;

use App\Models\PoolTable;
use App\Models\PricingRule;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- BERSIHKAN DATA LAMA AGAR TIDAK BENTROK/DUPLIKAT ---
        Schema::disableForeignKeyConstraints();
        User::truncate();
        PoolTable::truncate();
        PricingRule::truncate();
        Package::truncate();
        Schema::enableForeignKeyConstraints();

        // --- 1. SEED USERS ---
        // Buat user untuk login admin dan owner
        User::updateOrCreate([
            'name' => 'Sajid',
            'username' => 'sajid',
            'password' => Hash::make('sajid'),
            'role' => 'master',
        ]);

        User::updateOrCreate([
            'name' => 'Unyil',
            'username' => 'unyil',
            'password' => Hash::make('admin'),
            'role' => 'admin',
        ]);

        // --- 2. SEED POOL TABLES (14 Meja Sekaligus) ---
        // Ini bagian yang akan menampilkan 14 meja di dashboard admin kamu
        for ($i = 1; $i <= 14; $i++) {
            PoolTable::updateOrCreate([
                'table_number' => $i,
                'relay_channel' => $i, // Untuk keperluan IoT/Relay nanti
                'status' => 'available', // Status default: Abu-abu (Available)
                'is_active' => true,
            ]);
        }

        // --- 3. SEED PRICING RULES (Harga Reguler) ---
        $rules = [
            [
                'name' => 'Day Weekday',
                'day' => 'weekday',
                'start' => '11:00:00',
                'end' => '17:59:59',
                'price' => 27000,
                'active_days' => '1,2,3,4,5' // Senin sampai Jumat
            ],
            [
                'name' => 'Night Weekday',
                'day' => 'weekday',
                'start' => '18:00:00',
                'end' => '03:00:00',
                'price' => 38000,
                'active_days' => '1,2,3,4,5' // Senin sampai Jumat
            ],
            [
                'name' => 'Day Weekend',
                'day' => 'weekend',
                'start' => '11:00:00',
                'end' => '17:59:59',
                'price' => 29000,
                'active_days' => '6,7' // Sabtu dan Minggu
            ],
            [
                'name' => 'Night Weekend',
                'day' => 'weekend',
                'start' => '18:00:00',
                'end' => '03:00:00',
                'price' => 43000,
                'active_days' => '6,7' // Sabtu dan Minggu
            ],
        ];

        foreach ($rules as $rule) {
            PricingRule::updateOrCreate([
                'name' => $rule['name'],
                'day_type' => $rule['day'],
                'start_time' => $rule['start'],
                'end_time' => $rule['end'],
                'price_per_hour' => $rule['price'],
                'active_days' => $rule['active_days'], // Tambahkan baris ini
                'min_charge' => 10000 // Nilai default minimum charge
            ]);
        }

        // --- 4. SEED PACKAGES (Sesuai Poster Andromeda) ---
        // Galaxy Mix Combo: 110K (4 Jam + Mix Platter)
        Package::updateOrCreate([
            'name' => 'Galaxy Mix Combo',
            'price' => 110000,
            'day_type' => 'weekday',
            'active_from' => '11:00:00',
            'active_to' => '15:00:00',
            'duration_type' => 'minutes',
            'duration_value' => '240',
        ]);

        // Vitgo: 50K (2 Jam + 2 Vit)
        Package::updateOrCreate([
            'name' => 'Vitgo',
            'price' => 50000,
            'day_type' => 'weekday',
            'active_from' => '11:30:00',
            'active_to' => '17:00:00',
            'duration_type' => 'minutes',
            'duration_value' => '120',
        ]);

        $this->call([
            FnbSeeder::class,
        ]);
    }
}
