<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('value', 50);
            $table->timestamps();
        });

        // Default seeder
        DB::table('settings')->insert([
            [
                'key' => 'verification_time',
                'value' => '20',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'max_online_queue',
                'value' => '15',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'nearly_warning_minutes',
                'value' => '10',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'regist_wl_website',
                'value' => '1',
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
