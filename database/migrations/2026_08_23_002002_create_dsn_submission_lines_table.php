<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dsn_submission_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dsn_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['dsn_submission_id', 'payslip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsn_submission_lines');
    }
};
