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
        Schema::create('bim_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bim_model_id')->constrained('bim_models')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('element_name')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('quantity_required', 12, 4);
            $table->timestamps();

            $table->index(['bim_model_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bim_quantities');
    }
};
