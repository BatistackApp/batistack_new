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
        Schema::create('wage_garnishments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->decimal('total_amount_due', 10, 2);
            $table->decimal('amount_collected', 10, 2)->default(0);
            $table->decimal('monthly_deduction', 10, 2)->nullable();
            $table->date('start_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wage_garnishments');
    }
};
