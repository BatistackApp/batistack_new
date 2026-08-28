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
        Schema::create('manufacturing_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->unique();
            $table->foreignId('item_id')->constrained('items')->onDelete('restrict');
            $table->foreignId('chantier_id')->nullable()->constrained('chantiers')->onDelete('set null');

            $table->decimal('quantity_planned', 10, 4);
            $table->decimal('quantity_produced', 10, 4)->default(0);

            $table->string('status')->default('draft');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->decimal('total_labor_cost', 10, 2)->default(0);
            $table->decimal('total_material_cost', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manufacturing_orders');
    }
};
