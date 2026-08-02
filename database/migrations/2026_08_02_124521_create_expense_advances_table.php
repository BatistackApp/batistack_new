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
        Schema::create('expense_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('request_date');
            $table->string('reason');
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, PAID, DEDUCTED, REJECTED
            $table->foreignId('expense_report_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('payment_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_advances');
    }
};
