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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('digiposte_id')->nullable();
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->string('digiposte_document_id')->nullable();
            $table->string('digiposte_status')->default('pending'); // pending, deposited, failed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('digiposte_id');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->dropColumn(['digiposte_document_id', 'digiposte_status']);
        });
    }
};
