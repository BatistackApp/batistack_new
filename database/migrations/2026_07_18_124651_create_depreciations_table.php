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
        Schema::create('depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->date('period_date'); // Date de la dotation (ex: 31/12/2026)
            $table->decimal('amount', 15, 2); // Montant de la dotation
            $table->decimal('remaining_vnc', 15, 2); // VNC restante après dotation
            $table->boolean('is_passed')->default(false); // Validée/Passée en compta
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depreciations');
    }
};
