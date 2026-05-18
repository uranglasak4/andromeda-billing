<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    // Cek dulu, kalau tabel BELUM ada, baru buat
    if (!Schema::hasTable('fnb_categories')) {
        Schema::create('fnb_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
    }

    if (!Schema::hasTable('fnb_products')) {
        Schema::create('fnb_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fnb_category_id')->constrained('fnb_categories')->onDelete('cascade');
            $table->string('name');
            $table->integer('price');
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_tables');
    }
};
