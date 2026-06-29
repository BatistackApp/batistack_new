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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->date('expected_delivery_date')->nullable()->after('ordered_at');
        });

        Schema::table('receipt_notes', function (Blueprint $table) {
            $table->tinyInteger('quality_rating')->nullable()->comment('1 to 5')->after('received_at');
            $table->boolean('has_litigation')->default(false)->after('quality_rating');
        });

        Schema::table('third_parties', function (Blueprint $table) {
            $table->tinyInteger('supplier_score')->nullable()->comment('0 to 100')->after('credit_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('expected_delivery_date');
        });

        Schema::table('receipt_notes', function (Blueprint $table) {
            $table->dropColumn(['quality_rating', 'has_litigation']);
        });

        Schema::table('third_parties', function (Blueprint $table) {
            $table->dropColumn('supplier_score');
        });
    }
};
