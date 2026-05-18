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
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('day_type', ['weekday', 'weekend']);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('price_per_hour');
            $table->timestamps();
            // Tambahkan kolom untuk minimum charge (Rp 10.000)
    $table->integer('min_charge')->default(10000)->after('price_per_hour');
    // Kolom untuk menentukan hari apa saja (misal: "1,2,3,4" untuk Sen-Kam)
    $table->string('active_days')->nullable()->after('day_type');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pricing_rules');
    }
};
