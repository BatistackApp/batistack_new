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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('period'); // e.g. "2026-05"
            $table->decimal('base_hours', 8, 2);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('hourly_rate', 8, 4);
            $table->decimal('gross_salary', 10, 2);
            $table->decimal('net_social', 10, 2)->nullable();
            $table->decimal('taxable_net', 10, 2)->nullable();
            $table->decimal('pas_rate', 5, 4)->default(0);
            $table->decimal('pas_amount', 10, 2)->default(0);
            $table->decimal('net_payable', 10, 2)->nullable();
            $table->decimal('net_paid', 10, 2)->nullable();
            $table->decimal('employer_cost', 10, 2)->nullable();
            $table->string('status')->default('draft'); // draft, validated, paid
            $table->string('pdf_path')->nullable();
            $table->date('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
