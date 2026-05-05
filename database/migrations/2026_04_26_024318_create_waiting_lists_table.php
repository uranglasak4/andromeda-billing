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
        Schema::create('waiting_lists', function (Blueprint $table) {
    $table->id();
    $table->string('customer_name');
    $table->string('phone_number')->nullable();
    $table->string('otp', 4)->nullable(); // Untuk 4 digit OTP
    $table->enum('registered_via', ['admin', 'website'])->default('admin');
    $table->enum('status', [
        'pending',
        'waiting',
        'run',
        'expired',
        'finished'
    ])->default('waiting');
    $table->timestamp('expires_at')->nullable(); // Batas 15 menit untuk pendaftar web
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waiting_lists');
    }
};
