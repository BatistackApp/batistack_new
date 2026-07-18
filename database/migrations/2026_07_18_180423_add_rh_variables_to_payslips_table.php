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
        Schema::table('payslips', function (Blueprint $table) {
            $table->decimal('overtime_amount', 10, 2)->default(0)->after('overtime_hours');
            $table->decimal('gd_allowance_amount', 10, 2)->default(0)->after('overtime_amount');
            $table->decimal('expense_reports_amount', 10, 2)->default(0)->after('gd_allowance_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['overtime_amount', 'gd_allowance_amount', 'expense_reports_amount']);
        });
    }
};
