<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->unique()->after('serial_number');
        });

        Schema::table('equipements', function (Blueprint $table) {
            $table->string('qr_token')->nullable()->unique()->after('barcode');
        });

        DB::table('fixed_assets')
            ->select('id')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('fixed_assets')
                        ->where('id', $row->id)
                        ->update(['qr_token' => 'FA-'.strtoupper(Str::random(12))]);
                }
            });

        DB::table('equipements')
            ->select('id')
            ->whereNull('qr_token')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('equipements')
                        ->where('id', $row->id)
                        ->update(['qr_token' => 'EQ-'.strtoupper(Str::random(12))]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });

        Schema::table('equipements', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
