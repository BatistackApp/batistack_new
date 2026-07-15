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
        Schema::create('payroll_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_export_id')->constrained('payroll_exports')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('base_hours', 8, 2)->default(0);
            $table->decimal('worked_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('absence_days', 8, 2)->default(0);
            $table->decimal('travel_allowances', 10, 2)->default(0);
            $table->decimal('expense_reports_total', 10, 2)->default(0);
            $table->decimal('estimated_gross_salary', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_export_id', 'employee_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_variables');
    }
};
