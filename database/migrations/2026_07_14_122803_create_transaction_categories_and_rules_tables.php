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
        Schema::create('transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('type')->default('expense'); // expense, income
            $table->timestamps();
        });

        Schema::create('categorization_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_category_id')->constrained('transaction_categories')->cascadeOnDelete();
            $table->string('keyword');
            $table->timestamps();
        });

        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->foreignId('transaction_category_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->dropForeign(['transaction_category_id']);
            $table->dropColumn('transaction_category_id');
        });
        
        Schema::dropIfExists('categorization_rules');
        Schema::dropIfExists('transaction_categories');
    }
};
