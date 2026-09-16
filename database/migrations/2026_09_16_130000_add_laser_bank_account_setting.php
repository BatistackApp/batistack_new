<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->insertOrIgnore(
            ['key' => 'laser_bank_account'],
            ['value' => '512000', 'group' => 'banking', 'type' => 'string', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'laser_bank_account')->delete();
    }
};
