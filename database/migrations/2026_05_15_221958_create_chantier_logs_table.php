<?php

use App\Models\Chantiers\Chantier;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chantier_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Chantier::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('content');
            $table->string('weather_condition')->nullable();
            $table->boolean('incident_reported')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chantier_logs');
    }
};
