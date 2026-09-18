<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laser_order_id')->constrained('laser_orders')->cascadeOnDelete();
            $table->foreignId('material_id')->constrained('laser_materials')->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('length_mm', 10, 2)->default(0);
            $table->decimal('width_mm', 10, 2)->default(0);
            $table->decimal('thickness_mm', 6, 2)->default(0);
            $table->integer('quantity')->default(1);
            $table->decimal('surface_mm2', 14, 4)->default(0);
            $table->decimal('cut_length_mm', 10, 2)->default(0);
            $table->decimal('weight_kg', 10, 4)->default(0);
            $table->decimal('price_per_kg', 10, 4)->default(0);
            $table->decimal('price_per_meter', 10, 4)->default(0);
            $table->decimal('programming_cost', 10, 2)->default(0);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('unit_price_ht', 10, 4)->default(0);
            $table->decimal('total_ht', 10, 2)->default(0);
            $table->decimal('density_kg_m3', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_order_lines');
    }
};
