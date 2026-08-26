<?php

use App\Models\Chantiers\ChantierPhase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chantier_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ChantierPhase::class)->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->decimal('estimated_hours')->default(0);
            $table->integer('progress_percentage')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chantier_tasks');
    }
};
