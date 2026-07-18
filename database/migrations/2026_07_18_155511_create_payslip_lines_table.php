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
        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('label');
            $table->decimal('base', 10, 2);
            $table->decimal('employee_rate', 8, 4)->default(0);
            $table->decimal('employer_rate', 8, 4)->default(0);
            $table->decimal('employee_amount', 10, 2)->default(0);
            $table->decimal('employer_amount', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
    }
};
