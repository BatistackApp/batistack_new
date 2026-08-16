<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reference_counters', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('prefix', 10);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->unique(['year', 'prefix']);
        });

        $maxes = ['TK' => [], 'MC' => []];

        foreach (['TK' => 'asset_maintenance_tickets', 'MC' => 'maintenance_contracts'] as $prefix => $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->select(['reference', 'created_at'])->orderBy('id')->chunkById(500, function ($rows) use (&$maxes, $prefix) {
                foreach ($rows as $row) {
                    if (preg_match('/^'.$prefix.'-(\d{4})-(\d+)$/', (string) $row->reference, $m)) {
                        $year = (int) $m[1];
                        $maxes[$prefix][$year] = max($maxes[$prefix][$year] ?? 0, (int) $m[2]);
                    }
                }
            });
        }

        foreach ($maxes as $prefix => $years) {
            $years[now()->year] = $years[now()->year] ?? 0;

            foreach ($years as $year => $last) {
                DB::table('reference_counters')->insertOrIgnore([
                    'year' => $year,
                    'prefix' => $prefix,
                    'last_number' => $last,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reference_counters');
    }
};
