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
        Schema::create('inventory_cycle_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->decimal('theoretical_quantity', 10, 4);
            $table->decimal('counted_quantity', 10, 4)->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->unique(['inventory_cycle_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_cycle_lines');
    }
};
