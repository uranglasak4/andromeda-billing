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
            $table->string('active_days')->nullable(); // Taruh di sini tanpa ->after()
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('price_per_hour');
            $table->integer('min_charge')->default(10000); // Taruh di sini tanpa ->after()
            $table->timestamps();
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
