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
        Schema::create('payroll_contribution_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_contribution_profile_id')->constrained('payroll_contribution_profiles', 'id', 'fk_pcr_profile_id')->cascadeOnDelete();
            $table->string('category'); // e.g. "Santé", "Retraite"
            $table->string('label'); // e.g. "Sécurité Sociale plafonnée"
            $table->decimal('employee_rate', 8, 4)->default(0); // in percentage
            $table->decimal('employer_rate', 8, 4)->default(0); // in percentage
            $table->string('base_formula')->default('gross_salary'); // e.g. "gross_salary", "ss_plafond", "oppbtp_base", "csg_base"
            $table->boolean('is_deductible')->default(true); // useful for CSG
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_contribution_rates');
    }
};
