<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laser_legalization_sequences', function (Blueprint $table) {
            $table->id();
            $table->text('last_hash')->default('GENESIS');
            $table->string('last_reference')->nullable();
            $table->timestamps();
        });

        DB::table('laser_legalization_sequences')->insert([
            'last_hash' => 'GENESIS',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('laser_legalization_sequences');
    }
};
