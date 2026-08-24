<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compte_comptables', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 10)->unique();
            $table->string('libelle');
            $table->unsignedTinyInteger('classe');
            $table->boolean('is_balance')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('compte_comptables')->nullOnDelete();
            $table->timestamps();

            $table->index('classe');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte_comptables');
    }
};
