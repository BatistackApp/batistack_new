<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('license_plate')->unique();
            $table->string('brand');
            $table->string('model');
            $table->string('type');
            $table->string('usage_unit')->default('km');
            $table->string('fuel_type');

            $table->decimal('odometer', 12, 2)->default(0);
            $table->string('status')->default('available');
            $table->date('pollution_control_due_at')->nullable();
            $table->string('current_location')->nullable();

            $table->decimal('daily_rate', 10, 2)->default(0);
            $table->decimal('km_rate', 6, 4)->default(0);

            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('tco_cache', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
