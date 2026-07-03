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
        Schema::table('third_parties', function (Blueprint $table) {
            $table->string('financial_status')->nullable()->after('siret');
            $table->json('financial_data')->nullable()->after('financial_status');
            $table->timestamp('last_financial_sync_at')->nullable()->after('financial_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('third_parties', function (Blueprint $table) {
            $table->dropColumn(['financial_status', 'financial_data', 'last_financial_sync_at']);
        });
    }
};
