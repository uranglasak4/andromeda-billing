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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 35);
            $table->string('username', 20)->unique();
            $table->string('password', 100);
            $table->enum('role', ['master', 'admin'])->default('admin'); // Role Owner & Kasir
            $table->boolean('is_active')->default(true); // Status aktif/nonaktif
            $table->timestamp('last_login_at')->nullable(); // Waktu login terakhir
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
