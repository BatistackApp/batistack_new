<?php

use App\Models\Chantiers\Chantier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doe_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Chantier::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->boolean('is_validated')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doe_documents');
    }
};
