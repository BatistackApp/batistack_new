<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_rental_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
            $table->foreignId('chantier_id')->constrained('chantiers')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('days');
            $table->decimal('daily_rate', 15, 2);
            $table->decimal('amount_ht', 15, 2);
            $table->string('status')->default('draft');
            $table->string('billing_key')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_rental_invoices');
    }
};