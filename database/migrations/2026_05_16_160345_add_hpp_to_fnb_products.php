<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fnb_products', function (Blueprint $table) {
    $table->integer('hpp')->default(0)->after('price'); // Kita pakai nama 'hpp' sesuai istilah manajer
    $table->integer('min_stock')->default(5)->after('stock');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_products', function (Blueprint $table) {
            //
        });
    }
};
