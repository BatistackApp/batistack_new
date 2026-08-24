<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecriture_comptables', function (Blueprint $table) {
            $table->id();
            $table->date('date_ecriture');
            $table->date('date_piece');
            $table->string('journal_type', 10);
            $table->string('numero_piece', 50);
            $table->string('compte_numero', 10);
            $table->string('libelle');
            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);
            $table->string('lettrage', 10)->nullable();
            $table->string('lettrage_status', 30)->default('non_lettree');
            $table->nullableMorphs('reconcilable');
            $table->foreignId('chantier_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('date_ecriture');
            $table->index('journal_type');
            $table->index('compte_numero');
            $table->index('lettrage');
            $table->index('lettrage_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecriture_comptables');
    }
};
