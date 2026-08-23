<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dsn_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->string('period'); // "2026-07"
            $table->string('status')->default('draft');
            $table->string('export_type'); // csv_expert, api_m2m
            $table->datetime('submitted_at')->nullable();
            $table->datetime('exported_at')->nullable();
            $table->text('error_message')->nullable();
            $table->string('exported_file_path')->nullable();
            $table->integer('payslips_count')->default(0);
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->decimal('total_employer_cost', 12, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['company_id', 'period', 'export_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dsn_submissions');
    }
};
