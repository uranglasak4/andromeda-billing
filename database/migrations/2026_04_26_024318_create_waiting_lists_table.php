<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone_number')->nullable();
            $table->string('otp', 4)->nullable();

            // Kolom Tipe Antrean
            $table->enum('tipe', ['onsite', 'online']);

            // Kolom Status Tunggal (Sudah Diperbaiki Komanya Jid!)
            $table->enum('status', [
                'waiting',       // Customer WL yang daftar Onsite (Langsung aktif)
                'not_verified',  // Customer daftar Online tetapi belum verifikasi OTP
                'verified',      // Online yang sudah sukses lapor & check-in ke kasir
                'call',          // Meja ready, kasir panggil nama customer (Kirim WA giliran main)
                'expired',       // Online yang hangus otomatis karena telat lapor menit verifikasi
                'no_show'        // Onsite/Online yang dipanggil tapi orangnya kabur/tidak ada respon
            ])->default('waiting'); // Secara default default-nya 'waiting' (bisa kita override di Controller saat simpan)

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_lists');
    }
};
