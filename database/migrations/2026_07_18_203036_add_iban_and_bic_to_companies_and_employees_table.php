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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('iban', 34)->nullable();
            $table->string('bic', 11)->nullable();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('iban', 34)->nullable();
            $table->string('bic', 11)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['iban', 'bic']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['iban', 'bic']);
        });
    }
};
