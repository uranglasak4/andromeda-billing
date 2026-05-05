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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('price');
            $table->enum('day_type', ['weekday', 'weekend', 'both']);
            $table->time('active_from'); // Jam mulai promo tersedia
            $table->time('active_to');   // Jam akhir promo tersedia
            $table->enum('duration_type', ['minutes', 'fixed_end_time']); 
            $table->string('duration_value'); // Misal "120" atau "19:00"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
