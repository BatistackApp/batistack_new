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
        Schema::create('third_party_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('third_party_id')->constrained('third_parties')->cascadeOnDelete();
            $table->string('type'); // kbis, urssaf, decennale, autre
            $table->date('expiration_date')->nullable();
            $table->string('status')->default('valid'); // valid, expired
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('third_party_documents');
    }
};
