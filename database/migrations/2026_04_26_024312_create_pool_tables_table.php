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
        Schema::create('pool_tables', function (Blueprint $table) {
            $table->id();
            $table->integer('table_number')->unique();
            $table->integer('relay_channel')->unique(); // Pin Relay 1-16
            $table->enum('status', ['available', 'playing', 'nearly', 'personal', 'maintenance'])->default('available');
            $table->boolean('is_active')->default(true); // Permintaan dosen: bisa nambah meja
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pool_tables');
    }
};
