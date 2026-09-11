<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laser_orders', function (Blueprint $table) {
            $table->unique('laser_quote_id');
        });
    }

    public function down(): void
    {
        Schema::table('laser_orders', function (Blueprint $table) {
            $table->dropUnique('laser_orders_laser_quote_id_unique');
        });
    }
};
