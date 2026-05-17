<?php

use App\Models\Chantiers\Chantier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chantier_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Chantier::class)->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chantier_phases');
    }
};
