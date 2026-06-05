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
        Schema::create('fnb_products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fnb_category_id')->constrained('fnb_categories')->onDelete('cascade');
    $table->string('name'); // Nasi Goreng
    $table->integer('price'); // Harga Jual
    $table->integer('stock')->default(0); // Stok barang
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Matikan pengecekan foreign key sementara
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('fnb_products');

        // Hidupkan kembali pengecekan foreign key
        Schema::enableForeignKeyConstraints();
    }
};
