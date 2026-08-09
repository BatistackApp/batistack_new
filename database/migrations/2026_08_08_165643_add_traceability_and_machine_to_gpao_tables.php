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
            $table->string('batch_number')->nullable()->after('quantity_produced');
            $table->string('serial_number')->nullable()->after('batch_number');
            $table->foreignId('machine_id')->nullable()->after('customer_order_id')->constrained('machines')->nullOnDelete();
        });

        Schema::table('manufacturing_requirements', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('quantity_consumed');
            $table->string('serial_number')->nullable()->after('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_requirements', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'serial_number']);
        });

        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropColumn(['batch_number', 'serial_number', 'machine_id']);
        });
    }
};
