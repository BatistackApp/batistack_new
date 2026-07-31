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
        Schema::table('depreciations', function (Blueprint $table) {
            $table->decimal('grant_reversal_amount', 15, 2)->default(0)->after('amount');
            $table->decimal('grant_remaining_amount', 15, 2)->default(0)->after('grant_reversal_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depreciations', function (Blueprint $table) {
            $table->dropColumn(['grant_reversal_amount', 'grant_remaining_amount']);
        });
    }
};
