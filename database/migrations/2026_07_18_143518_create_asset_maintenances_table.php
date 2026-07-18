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
        Schema::create('asset_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->foreignId('chantier_id')->nullable()->constrained('chantiers')->nullOnDelete();
            $table->date('maintenance_date');
            $table->string('type'); // preventive, curative
            $table->text('description');
            $table->decimal('cost_ht', 10, 2)->default(0);
            $table->string('provider_name')->nullable();
            $table->string('invoice_ref')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_maintenances');
    }
};
