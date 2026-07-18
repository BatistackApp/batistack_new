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
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_id')->constrained('asset_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('salvage_value', 15, 2)->default(0); // Valeur résiduelle
            $table->string('depreciation_method')->default('linear');
            $table->integer('useful_life_years');
            $table->string('status')->default('active'); // \App\Enums\Immobilisation\AssetStatus
            $table->foreignId('supplier_invoice_id')->nullable()->constrained('supplier_invoices')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
