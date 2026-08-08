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
        Schema::create('machine_manufacturing_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->cascadeOnDelete();
            $table->foreignId('manufacturing_order_id')->constrained('manufacturing_orders')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->dropForeign(['machine_id']);
            $table->dropColumn('machine_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('manufacturing_orders', function (Blueprint $table) {
            $table->foreignId('machine_id')->nullable()->constrained('machines')->nullOnDelete();
        });
        
        Schema::dropIfExists('machine_manufacturing_order');
    }
};
