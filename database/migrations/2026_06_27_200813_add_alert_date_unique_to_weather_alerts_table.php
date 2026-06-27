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
        Schema::table('weather_alerts', function (Blueprint $table) {
            $table->date('alert_date')->nullable();
            $table->unique(['chantier_id', 'type', 'alert_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_alerts', function (Blueprint $table) {
            $table->dropUnique(['chantier_id', 'type', 'alert_date']);
            $table->dropColumn('alert_date');
        });
    }
};
