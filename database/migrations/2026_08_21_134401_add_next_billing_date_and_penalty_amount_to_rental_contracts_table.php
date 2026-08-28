<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->date('next_billing_date')->nullable()->after('expected_end_date');
            $table->decimal('penalty_amount', 15, 2)->default(0)->after('daily_penalty_rate');
        });

        DB::table('rental_contracts')
            ->where('status', 'active')
            ->whereNull('next_billing_date')
            ->update(['next_billing_date' => Carbon::today()->toDateString()]);
    }

    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropColumn(['next_billing_date', 'penalty_amount']);
        });
    }
};
