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
        Schema::table('stock_mouvements', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('description');
            $table->date('expiration_date')->nullable()->after('batch_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_mouvements', function (Blueprint $table) {
            $table->dropColumn(['batch_number', 'expiration_date']);
        });
    }
};
