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
        Schema::create('bim_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('file_path');
            $table->string('format', 10)->comment('ifc, gltf, obj, dxf');
            $table->bigInteger('file_size')->nullable()->comment('En octets');
            $table->integer('version')->default(1);
            $table->nullableMorphs('modelable'); // Pour l'attacher à un Chantier, un Article, etc.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bim_models');
    }
};
