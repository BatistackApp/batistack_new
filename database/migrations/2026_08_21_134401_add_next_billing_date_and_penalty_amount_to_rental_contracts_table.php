<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->date('next_billing_date')->nullable()->after('expected_end_date');
            $table->decimal('penalty_amount', 15, 2)->default(0)->after('daily_penalty_rate');
        });
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropColumn(['next_billing_date', 'penalty_amount']);
        });
    }
};