<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_payment_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->constrained('salary_payment_runs')->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending');
            $table->string('bridge_payment_request_id')->nullable();
            $table->string('consent_url')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('end_to_end_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payment_lines');
    }
};
