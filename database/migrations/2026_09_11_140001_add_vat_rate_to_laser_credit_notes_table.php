<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laser_credit_notes', function (Blueprint $table) {
            $table->decimal('vat_rate', 5, 2)->default(20)->after('total_ttc');
        });
    }

    public function down(): void
    {
        Schema::table('laser_credit_notes', function (Blueprint $table) {
            $table->dropColumn('vat_rate');
        });
    }
};
