<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->foreignId('laser_order_id')
                ->nullable()
                ->after('customer_order_id')
                ->constrained('laser_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->dropForeign(['laser_order_id']);
            $table->dropColumn('laser_order_id');
        });
    }
};
