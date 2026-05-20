<?php

namespace Database\Seeders;

use App\Models\FnbCategory; // Diperbaiki agar mengarah ke model yang benar
use App\Models\FnbProduct;  // Diperbaiki agar mengarah ke model yang benar
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FnbSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data lama FnB sebelum mengisi yang baru agar tidak duplikat
        Schema::disableForeignKeyConstraints();
        FnbCategory::truncate();
        FnbProduct::truncate();
        Schema::enableForeignKeyConstraints();

        // --- 1. SEED KATEGORI FnB (Sesuai Daftar Baru) ---
        $categories = [
            'soda_base'     => FnbCategory::create(['name' => 'Soda Base']),
            'coffee_base'   => FnbCategory::create(['name' => 'Coffee Base']),
            'coffee_flavor' => FnbCategory::create(['name' => 'Coffee Flavor']),
            'kopi_susu'     => FnbCategory::create(['name' => 'Kopi Susu']),
            'milk_base'     => FnbCategory::create(['name' => 'Milk Base']),
            'tea_base'      => FnbCategory::create(['name' => 'Tea Base']),
            'showcase'      => FnbCategory::create(['name' => 'Showcase']),
            'glove'         => FnbCategory::create(['name' => 'Glove']),
            'makanan'       => FnbCategory::create(['name' => 'Makanan']),
            'rokok'         => FnbCategory::create(['name' => 'Rokok']),
            'snack'         => FnbCategory::create(['name' => 'Snack']),
        ];

        // --- 2. SEED PRODUK (Sesuai Daftar & Aturan Hot/Cold) ---

        // Kategori: SODA BASE
        $sodaBase = [
            ['name' => 'Red Ladies', 'price' => 25000],
            ['name' => 'Mix Berry', 'price' => 25000],
            ['name' => 'Summer Peach', 'price' => 25000],
        ];
        foreach ($sodaBase as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['soda_base']->id, 'hpp' => $item['price'] * 0.6, 'stock' => 100, 'min_stock' => 5]));
        }

        // Kategori: COFFEE BASE
        $coffeeBase = [
            ['name' => 'Americano Hot', 'price' => 21000],
            ['name' => 'Americano Cold', 'price' => 21000],
            ['name' => 'Cappucino Hot', 'price' => 23000],
            ['name' => 'Cappucino Cold', 'price' => 23000],
            ['name' => 'Cafe Latte Hot', 'price' => 23000],
            ['name' => 'Cafe Latte Cold', 'price' => 23000],
            ['name' => 'Moccacino Hot', 'price' => 23000],
            ['name' => 'Moccacino Cold', 'price' => 23000],
        ];
        foreach ($coffeeBase as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['coffee_base']->id, 'hpp' => $item['price'] * 0.5, 'stock' => 100, 'min_stock' => 5]));
        }

        // Kategori: COFFEE FLAVOR
        $coffeeFlavor = [
            ['name' => 'Butterscotch', 'price' => 25000],
            ['name' => 'Hazelnut Latte', 'price' => 25000],
            ['name' => 'Caramel Latte', 'price' => 25000],
            ['name' => 'Vanilla Latte', 'price' => 25000],
            ['name' => 'Tiramisu Latte', 'price' => 25000],
            ['name' => 'Caramel Machiato', 'price' => 25000],
        ];
        foreach ($coffeeFlavor as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['coffee_flavor']->id, 'hpp' => $item['price'] * 0.5, 'stock' => 100, 'min_stock' => 5]));
        }

        // Kategori: KOPI SUSU
        $kopiSusu = [
            ['name' => 'Kopi Susu Original Hot', 'price' => 22000],
            ['name' => 'Kopi Susu Original Cold', 'price' => 22000],
            ['name' => 'Kopi Susu Aren', 'price' => 23000],
            ['name' => 'Kopi Susu Caramel', 'price' => 25000],
            ['name' => 'Kopi Susu Hazelnut', 'price' => 25000],
            ['name' => 'Kopi Susu Cheese', 'price' => 25000],
        ];
        foreach ($kopiSusu as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['kopi_susu']->id, 'hpp' => $item['price'] * 0.5, 'stock' => 100, 'min_stock' => 5]));
        }

        // Kategori: MILK BASE
        $milkBase = [
            ['name' => 'Chocolate Hot', 'price' => 22000],
            ['name' => 'Chocolate Cold', 'price' => 22000],
            ['name' => 'Vanilla Hot', 'price' => 22000],
            ['name' => 'Vanilla Cold', 'price' => 22000],
            ['name' => 'Matcha Hot', 'price' => 22000],
            ['name' => 'Matcha Cold', 'price' => 22000],
            ['name' => 'Taro Hot', 'price' => 22000],
            ['name' => 'Taro Cold', 'price' => 22000],
            ['name' => 'Sweet Blueberry', 'price' => 24000],
            ['name' => 'Strawberry Milky', 'price' => 24000],
            ['name' => 'Mocca', 'price' => 24000],
        ];
        foreach ($milkBase as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['milk_base']->id, 'hpp' => $item['price'] * 0.6, 'stock' => 100, 'min_stock' => 5]));
        }

        // Kategori: TEA BASE
        $teaBase = [
            ['name' => 'Lemon Tea Hot', 'price' => 20000],
            ['name' => 'Lemon Tea Cold', 'price' => 20000],
            ['name' => 'Lychee Tea', 'price' => 20000],
            ['name' => 'Peach Tea', 'price' => 20000],
        ];
        foreach ($teaBase as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['tea_base']->id, 'hpp' => $item['price'] * 0.4, 'stock' => 150, 'min_stock' => 10]));
        }

        // Kategori: SHOWCASE
        $showcase = [
            ['name' => 'Vit Mineral 600ml', 'price' => 5000],
            ['name' => 'Teh Botol', 'price' => 8000],
        ];
        foreach ($showcase as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['showcase']->id, 'hpp' => $item['price'] * 0.5, 'stock' => 200, 'min_stock' => 10]));
        }

        // Kategori: GLOVE
        $glove = [
            ['name' => 'Glove Premium', 'price' => 40000],
            ['name' => 'Glove Standard', 'price' => 30000],
        ];
        foreach ($glove as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['glove']->id, 'hpp' => $item['price'] * 0.6, 'stock' => 50, 'min_stock' => 5]));
        }

        // Kategori: MAKANAN
        $makanan = [
            ['name' => 'Nasi Ayam Geprek', 'price' => 28000],
            ['name' => 'Nasi Ayam Matah', 'price' => 28000],
            ['name' => 'Nasi Ayam Cabe Hijau', 'price' => 28000],
            ['name' => 'Nasi Ayam Black Pepper', 'price' => 28000],
            ['name' => 'Mie Rebus', 'price' => 18000],
            ['name' => 'Mie Rebus Creamy', 'price' => 23000],
            ['name' => 'Mie Goreng', 'price' => 18000],
        ];
        foreach ($makanan as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['makanan']->id, 'hpp' => $item['price'] * 0.6, 'stock' => 50, 'min_stock' => 5]));
        }

        // Kategori: ROKOK
        $rokok = [
            ['name' => 'Sampurna Mild *B (16 btg)', 'price' => 38000],
            ['name' => 'Sampurna Mild *K (12 btg)', 'price' => 29000],
        ];
        foreach ($rokok as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['rokok']->id, 'hpp' => $item['price'] * 0.85, 'stock' => 30, 'min_stock' => 2]));
        }

        // Kategori: SNACK
        $snack = [
            ['name' => 'French Fries', 'price' => 18000],
            ['name' => 'Sausage', 'price' => 18000],
            ['name' => 'Fish Roll', 'price' => 20000],
            ['name' => 'Nuggets', 'price' => 18000],
            ['name' => 'Mix Platter', 'price' => 25000],
        ];
        foreach ($snack as $item) {
            FnbProduct::create(array_merge($item, ['fnb_category_id' => $categories['snack']->id, 'hpp' => $item['price'] * 0.6, 'stock' => 80, 'min_stock' => 5]));
        }
    }
}
