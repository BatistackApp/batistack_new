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
            $table->json('custom_bonuses')->nullable()->after('expense_reports_amount');
            $table->decimal('meal_allowance_amount', 10, 2)->default(0)->after('custom_bonuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['custom_bonuses', 'meal_allowance_amount']);
        });
    }
};
