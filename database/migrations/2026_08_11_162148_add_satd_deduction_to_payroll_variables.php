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
        Schema::table('payroll_variables', function (Blueprint $table) {
            $table->decimal('satd_deduction', 10, 2)->default(0)->after('estimated_gross_salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_variables', function (Blueprint $table) {
            $table->dropColumn('satd_deduction');
        });
    }
};
