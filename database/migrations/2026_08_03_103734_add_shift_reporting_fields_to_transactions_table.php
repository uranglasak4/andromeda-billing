<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Menambahkan kolom baru tanpa menghapus tabel yang ada
            if (!Schema::hasColumn('transactions', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users');
            }
            if (!Schema::hasColumn('transactions', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('created_by')->constrained('users');
            }
            if (!Schema::hasColumn('transactions', 'bill_price')) {
                $table->integer('bill_price')->nullable()->after('duration');
            }
            if (!Schema::hasColumn('transactions', 'fnb_price')) {
                $table->integer('fnb_price')->default(0)->after('bill_price');
            }
            if (!Schema::hasColumn('transactions', 'grand_total')) {
                $table->integer('grand_total')->default(0)->after('fnb_price');
            }
            if (!Schema::hasColumn('transactions', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'qris', 'transfer'])->default('cash')->after('grand_total');
            }
            if (!Schema::hasColumn('transactions', 'pay_amount')) {
                $table->integer('pay_amount')->default(0)->after('payment_method');
            }
            if (!Schema::hasColumn('transactions', 'change_amount')) {
                $table->integer('change_amount')->default(0)->after('pay_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['closed_by']);
            $table->dropColumn([
                'created_by',
                'closed_by',
                'bill_price',
                'fnb_price',
                'grand_total',
                'payment_method',
                'pay_amount',
                'change_amount',
            ]);
        });
    }
};
