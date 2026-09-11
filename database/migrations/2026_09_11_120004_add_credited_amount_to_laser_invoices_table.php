<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laser_invoices', function (Blueprint $table) {
            $table->decimal('credited_amount_ht', 15, 2)->default(0)->after('total_ht');
            $table->decimal('credited_amount_tva', 15, 2)->default(0)->after('credited_amount_ht');
            $table->decimal('credited_amount_ttc', 15, 2)->default(0)->after('credited_amount_tva');
        });
    }

    public function down(): void
    {
        Schema::table('laser_invoices', function (Blueprint $table) {
            $table->dropColumn(['credited_amount_ht', 'credited_amount_tva', 'credited_amount_ttc']);
        });
    }
};
