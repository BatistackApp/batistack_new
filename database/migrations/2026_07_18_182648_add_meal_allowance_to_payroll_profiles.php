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
        Schema::table('payroll_contribution_profiles', function (Blueprint $table) {
            $table->decimal('meal_allowance_amount', 10, 2)->default(0)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_contribution_profiles', function (Blueprint $table) {
            $table->dropColumn('meal_allowance_amount');
        });
    }
};
