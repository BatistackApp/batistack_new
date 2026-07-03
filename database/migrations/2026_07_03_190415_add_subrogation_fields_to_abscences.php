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
        Schema::table('abscences', function (Blueprint $table) {
            $table->boolean('requires_subrogation')->default(false)->after('is_paid');
            $table->decimal('ij_expected', 10, 2)->nullable()->after('requires_subrogation');
            $table->decimal('ij_received', 10, 2)->default(0)->after('ij_expected');
            $table->date('ij_payment_date')->nullable()->after('ij_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abscences', function (Blueprint $table) {
            $table->dropColumn(['requires_subrogation', 'ij_expected', 'ij_received', 'ij_payment_date']);
        });
    }
};
