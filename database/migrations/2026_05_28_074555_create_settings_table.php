<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        // Langsung isi data default awal (Seeder otomatis)
        DB::table('settings')->insert([
            [
                'key' => 'verification_time',
                'value' => '20', // Default 20 Menit
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'max_online_queue',
                'value' => '15', // Default Maksimal 15 Antrean Online
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
