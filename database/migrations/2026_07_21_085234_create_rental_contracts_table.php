<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('third_parties')->cascadeOnDelete();
            $table->foreignId('chantier_id')->nullable()->constrained('chantiers')->nullOnDelete();
            
            $table->string('reference')->unique();
            $table->string('name');
            
            $table->date('start_date');
            $table->date('end_date')->nullable();
            
            $table->string('status')->default('draft');
            $table->string('billing_period')->default('monthly');
            
            $table->decimal('daily_cost_ht', 15, 2)->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_contracts');
    }
};
