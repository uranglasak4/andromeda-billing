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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Relasi ke Kasir / User
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');

            // Detail Meja & Pelanggan
            $table->foreignId('pool_table_id')->nullable()->constrained('pool_tables')->onDelete('cascade');
            $table->string('customer_name')->nullable();

            // Tipe Billing & Pricing
            $table->enum('billing_type', ['hourly', 'package', 'personal']);
            $table->foreignId('pricing_rule_id')->nullable()->constrained('pricing_rules');
            $table->foreignId('package_id')->nullable()->constrained('packages');

            // Waktu & Durasi
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable(); // Nullable untuk tipe 'personal'
            $table->integer('duration')->nullable();   // Durasi dalam menit

            // Detail Pembayaran & Harga
            $table->integer('bill_price')->nullable();
            $table->integer('fnb_price')->default(0);
            $table->integer('grand_total')->default(0);
            $table->enum('payment_method', ['cash', 'qris', 'transfer'])->default('cash');
            $table->integer('pay_amount')->default(0);
            $table->integer('change_amount')->default(0);

            // Status & Timestamps
            $table->enum('status', ['running', 'unpaid', 'finished', 'cancelled'])->default('running');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
