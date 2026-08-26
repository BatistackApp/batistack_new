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
        Schema::create('bim_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bim_model_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();

            // Coordonnées spatiales (X, Y, Z)
            $table->float('position_x')->nullable();
            $table->float('position_y')->nullable();
            $table->float('position_z')->nullable();

            // Caméra optionnelle
            $table->float('camera_x')->nullable();
            $table->float('camera_y')->nullable();
            $table->float('camera_z')->nullable();

            // Lien polymorphique vers une tâche ou une intervention liée
            $table->nullableMorphs('target');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bim_annotations');
    }
};
