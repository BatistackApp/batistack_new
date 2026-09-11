<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laser_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('laser_order_line_id')->constrained('laser_order_lines')->restrictOnDelete();
            $table->foreignId('material_id')->constrained('laser_materials')->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('length_mm', 10, 2);
            $table->decimal('width_mm', 10, 2);
            $table->decimal('thickness_mm', 6, 2);
            $table->integer('quantity');
            $table->integer('quantity_invoiced');
            $table->decimal('unit_price_ht', 10, 4);
            $table->decimal('discount_pct', 5, 2);
            $table->decimal('total_ht', 10, 2);
            $table->decimal('weight_kg', 10, 4);
            $table->decimal('density_kg_m3', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_invoice_lines');
    }
};
