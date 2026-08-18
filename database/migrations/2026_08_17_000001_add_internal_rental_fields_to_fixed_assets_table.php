<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->decimal('daily_rate', 15, 2)->nullable()->after('purchase_price');
            $table->string('internal_rental_period')->default('monthly')->after('daily_rate');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropColumn(['daily_rate', 'internal_rental_period']);
        });
    }
};