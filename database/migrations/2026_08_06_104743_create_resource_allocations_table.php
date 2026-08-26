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
        Schema::create('resource_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chantier_task_id')->constrained('chantier_tasks')->cascadeOnDelete();
            $table->morphs('allocatable'); // allocatable_id, allocatable_type
            $table->date('date');
            $table->timestamps();

            // Unicité : une ressource ne peut être affectée qu'une fois par jour (même tâche ou différente)
            // L'unicité sur la ressource + date empêchera de l'affecter à plusieurs tâches différentes le même jour
            $table->unique(['allocatable_id', 'allocatable_type', 'date'], 'resource_alloc_unique_daily');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_allocations');
    }
};
