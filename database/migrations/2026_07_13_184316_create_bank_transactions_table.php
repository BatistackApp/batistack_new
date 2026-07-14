<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->nullable()->index(); // Transaction ID from OFX or Bankin
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 12, 2); // Signed amount. Positive = Credit, Negative = Debit.
            $table->string('type'); // 'credit' or 'debit'
            $table->string('status')->default('pending'); // pending, reconciled, ignored
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
