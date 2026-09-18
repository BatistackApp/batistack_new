<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('density_kg_m3', 10, 2);
            $table->decimal('price_per_kg', 10, 4);
            $table->decimal('price_per_meter', 10, 4);
            $table->decimal('min_thickness_mm', 8, 2);
            $table->decimal('max_thickness_mm', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_materials');
    }
};
