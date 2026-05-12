<?php

use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->decimal('purchase_price', 15, 4)->default(0);
            $table->decimal('selling_price', 15, 4)->default(0);
            $table->decimal('min_stock', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(Unit::class)->constrained();
            $table->foreignIdFor(VatRate::class)->constrained();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
