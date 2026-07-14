<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('checking');
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();
            $table->string('currency')->default('EUR');
            $table->decimal('balance', 12, 2)->default(0);
            $table->string('bankin_item_id')->nullable()->index(); // ID of the account in Bankin API
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
