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
        Schema::table('payroll_contribution_rates', function (Blueprint $table) {
            $table->boolean('is_fiscally_reintegrated')->default(false)->after('is_deductible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payroll_contribution_rates', function (Blueprint $table) {
            $table->dropColumn('is_fiscally_reintegrated');
        });
    }
};
