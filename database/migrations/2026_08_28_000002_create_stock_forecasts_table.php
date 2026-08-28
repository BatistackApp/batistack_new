<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->timestamp('forecasted_at');
            $table->integer('days_until_rupture')->nullable();
            $table->decimal('daily_burn', 12, 4)->default(0);
            $table->decimal('seasonality_coeff', 5, 4)->default(1);
            $table->decimal('planned_needs', 12, 4)->default(0);
            $table->decimal('available_stock', 12, 4)->default(0);
            $table->decimal('suggested_qty', 12, 4)->default(0);
            $table->date('suggested_order_date')->nullable();
            $table->enum('confidence', ['low', 'med', 'high'])->default('low');
            $table->timestamps();

            $table->index(['item_id', 'suggested_order_date']);
            $table->index('confidence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_forecasts');
    }
};
