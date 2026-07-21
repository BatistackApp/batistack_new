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
        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->foreignId('customer_order_id')->nullable()->after('item_id')->constrained('customer_orders')->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->after('customer_order_id')->constrained('manufacturing_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['customer_order_id']);
            $table->dropColumn(['parent_id', 'customer_order_id']);
        });
    }
};
