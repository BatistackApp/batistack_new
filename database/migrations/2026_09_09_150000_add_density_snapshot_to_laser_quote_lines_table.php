<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laser_quote_lines', function (Blueprint $table) {
            $table->decimal('density_kg_m3', 10, 2)->after('material_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('laser_quote_lines', function (Blueprint $table) {
            $table->dropColumn('density_kg_m3');
        });
    }
};
