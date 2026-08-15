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
        Schema::table('customer_quotes', function (Blueprint $table) {
            $table->boolean('is_avenant')->default(false)->after('reference');
            $table->unsignedBigInteger('parent_order_id')->nullable()->after('is_avenant');

            $table->foreign('parent_order_id')
                ->references('id')
                ->on('customer_orders')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_quotes', function (Blueprint $table) {
            $table->dropForeign(['parent_order_id']);
            $table->dropColumn(['parent_order_id', 'is_avenant']);
        });
    }
};
