<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chantier_subcontractors', function (Blueprint $table) {
            $table->foreignId('chantier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('third_party_id')->constrained('third_parties')->cascadeOnDelete();
            $table->primary(['chantier_id', 'third_party_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chantier_subcontractors');
    }
};
