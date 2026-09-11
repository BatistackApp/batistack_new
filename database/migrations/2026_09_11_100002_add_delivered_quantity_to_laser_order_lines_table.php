<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laser_order_lines', function (Blueprint $table) {
            $table->integer('delivered_quantity')->default(0)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('laser_order_lines', function (Blueprint $table) {
            $table->dropColumn('delivered_quantity');
        });
    }
};
