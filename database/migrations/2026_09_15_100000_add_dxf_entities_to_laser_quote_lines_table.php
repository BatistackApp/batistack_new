<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laser_quote_lines', function (Blueprint $table) {
            $table->json('dxf_entities')->nullable()->after('density_kg_m3');
        });
    }

    public function down(): void
    {
        Schema::table('laser_quote_lines', function (Blueprint $table) {
            $table->dropColumn('dxf_entities');
        });
    }
};
