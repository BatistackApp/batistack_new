<?php

use App\Models\Laser\LaserMaterial;
use App\Models\Laser\LaserQuote;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_quote_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(LaserQuote::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(LaserMaterial::class, 'material_id')->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->decimal('length_mm', 10, 2);
            $table->decimal('width_mm', 10, 2);
            $table->decimal('thickness_mm', 8, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('surface_mm2', 12, 4)->default(0);
            $table->decimal('cut_length_mm', 12, 2)->default(0);
            $table->decimal('weight_kg', 10, 4)->default(0);
            $table->decimal('price_per_kg', 10, 4);
            $table->decimal('price_per_meter', 10, 4);
            $table->decimal('programming_cost', 10, 2)->default(0);
            $table->decimal('discount_pct', 5, 2)->default(0);
            $table->decimal('unit_price_ht', 10, 4)->default(0);
            $table->decimal('total_ht', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_quote_lines');
    }
};
