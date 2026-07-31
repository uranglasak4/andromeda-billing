<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\FnbProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan tabel packages dan tabel pivot-nya
        Schema::disableForeignKeyConstraints();
        Package::truncate();
        if (Schema::hasTable('package_fnb_product')) {
            DB::table('package_fnb_product')->truncate();
        }
        Schema::enableForeignKeyConstraints();

        // 1. Ambil Referensi Produk FnB dari Database (Disesuaikan dengan FnbSeeder baru)
        $vitMineral   = FnbProduct::where('name', 'LIKE', '%Vit Mineral%')->first();
        $tehBotol     = FnbProduct::where('name', 'LIKE', '%Teh Botol%')->first();

        // DISESUAIKAN: Menghilangkan 'Cold' agar cocok dengan 'Kopi Susu Original'
        $kopiSusuOri  = FnbProduct::where('name', 'LIKE', '%Kopi Susu Original%')->first();

        $mixPlatter   = FnbProduct::where('name', 'LIKE', '%Mix Platter%')->first();
        $riceBowl     = FnbProduct::where('name', 'LIKE', '%Nasi Ayam Geprek%')->first();
        $mieGoreng    = FnbProduct::where('name', 'LIKE', '%Mie Goreng%')->first();

        // 2. Daftar 9 Paket (Menggunakan 'stock' sebagai nama kolom pivot)
        $packagesData = [
            [
                'name'           => 'Galaxy Mix Combo',
                'price'          => 110000,
                'active_from'    => '11:00:00',
                'active_to'      => '15:00:00',
                'duration_value' => 240,
                'items'          => [
                    $mixPlatter?->id => ['stock' => 1]
                ]
            ],
            [
                'name'           => 'Vitgo',
                'price'          => 50000,
                'active_from'    => '11:30:00',
                'active_to'      => '17:00:00',
                'duration_value' => 120,
                'items'          => [
                    $vitMineral?->id => ['stock' => 2]
                ]
            ],
            [
                'name'           => 'Nebulo',
                'price'          => 55000,
                'active_from'    => '11:30:00',
                'active_to'      => '17:00:00',
                'duration_value' => 120,
                'items'          => [
                    $tehBotol?->id => ['stock' => 2]
                ]
            ],
            [
                'name'           => 'Kosmo',
                'price'          => 75000,
                'active_from'    => '11:30:00',
                'active_to'      => '17:00:00',
                'duration_value' => 120,
                'items'          => [
                    $kopiSusuOri?->id => ['stock' => 2]
                ]
            ],
            [
                'name'           => 'Plater',
                'price'          => 85000,
                'active_from'    => '11:30:00',
                'active_to'      => '17:00:00',
                'duration_value' => 120,
                'items'          => [
                    $mixPlatter?->id => ['stock' => 1],
                    $vitMineral?->id => ['stock' => 2]
                ]
            ],
            [
                'name'           => 'Auroram Combo',
                'price'          => 90000,
                'active_from'    => '11:30:00',
                'active_to'      => '17:00:00',
                'duration_value' => 120,
                'items'          => [
                    $riceBowl?->id    => ['stock' => 1],
                    $kopiSusuOri?->id => ['stock' => 1]
                ]
            ],
            [
                'name'           => 'Auroram',
                'price'          => 90000,
                'active_from'    => '11:30:00',
                'active_to'      => '17:00:00',
                'duration_value' => 120,
                'items'          => [
                    $riceBowl?->id => ['stock' => 2]
                ]
            ],
            [
                'name'           => 'Noodle Orbit',
                'price'          => 95000,
                'active_from'    => '11:30:00',
                'active_to'      => '16:00:00',
                'duration_value' => 180,
                'items'          => [
                    $mieGoreng?->id => ['stock' => 2]
                ]
            ],
            [
                'name'           => 'Chicken Orbit',
                'price'          => 115000,
                'active_from'    => '11:30:00',
                'active_to'      => '16:00:00',
                'duration_value' => 180,
                'items'          => [
                    $riceBowl?->id => ['stock' => 2]
                ]
            ],
        ];

        // 3. Loop simpan data
        foreach ($packagesData as $pkgData) {
            $package = Package::create([
                'name'           => $pkgData['name'],
                'price'          => $pkgData['price'],
                'day_type'       => 'weekday',
                'active_from'    => $pkgData['active_from'],
                'active_to'      => $pkgData['active_to'],
                'duration_type'  => 'minutes',
                'duration_value' => $pkgData['duration_value'],
            ]);

            // Filter key agar ID yang null tidak ikut disinkronkan ke pivot
            $attachItems = [];
            foreach ($pkgData['items'] as $productId => $pivotData) {
                if (!empty($productId)) {
                    $attachItems[$productId] = $pivotData;
                }
            }

            if (!empty($attachItems)) {
                $package->fnbProducts()->sync($attachItems);
            }
        }
    }
}
