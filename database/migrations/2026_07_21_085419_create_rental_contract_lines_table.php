<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_contract_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_contract_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price_ht', 15, 2);
            $table->decimal('total_price_ht', 15, 2)->storedAs('quantity * unit_price_ht');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contract_lines');
    }
};
