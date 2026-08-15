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
        Schema::create('asset_maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->morphs('asset');
            $table->foreignId('chantier_id')->nullable()->constrained('chantiers')->nullOnDelete();
            $table->foreignId('reported_by_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('reference')->unique();
            $table->text('description')->nullable();
            $table->string('severity')->default('medium');
            $table->string('status')->default('open');
            $table->decimal('cost_ht', 10, 2)->nullable();
            $table->string('provider_name')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_maintenance_tickets');
    }
};
