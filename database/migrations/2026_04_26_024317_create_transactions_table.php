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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('pool_table_id')->constrained('pool_tables');
            $table->string('customer_name')->nullable();
        
        // Tipe billing: jam-jaman, paket, atau open table (personal)
            $table->enum('billing_type', ['hourly', 'package', 'personal']); 
            $table->foreignId('pricing_rule_id')->nullable()->constrained('pricing_rules');
            $table->foreignId('package_id')->nullable()->constrained('packages');
        
            $table->dateTime('start_time'); 
            $table->dateTime('end_time')->nullable(); // Nullable untuk tipe 'personal'
            $table->integer('duration')->nullable();   // Durasi dalam menit
            $table->integer('total_price')->nullable(); // Nullable sampai meja di-close
            $table->enum('status', ['running', 'finished', 'cancelled'])->default('running');
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
