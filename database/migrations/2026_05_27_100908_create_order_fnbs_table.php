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
    Schema::create('order_fnbs', function (Blueprint $table) {
        $table->id();
        // nullable() karena Tipe 2 & 3 tidak punya transaksi billing meja berjalan
        $table->foreignId('transaction_id')->nullable()->constrained('transactions')->onDelete('cascade');
        $table->foreignId('fnb_product_id')->nullable()->constrained('fnb_products')->onDelete('set null');

        $table->string('customer_name')->nullable(); // Untuk mencatat nama customer Waiting List / Walk-In
        $table->integer('qty');
        $table->decimal('price', 15, 2);
        $table->decimal('subtotal', 15, 2);

        // 'unpaid' untuk tipe 1 (masuk bill meja), 'paid' untuk tipe 2 & 3 (langsung bayar di awal)
        $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_fnbs');
    }
};
