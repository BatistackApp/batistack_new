<?php

use App\Models\Chantiers\Chantier;
use App\Models\RH\Employee;
use App\Models\RH\Equipement;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipement_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Equipement::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Chantier::class)->nullable()->constrained()->nullOnDelete();
            $table->datetime('assigned_at');
            $table->datetime('returned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipement_assignments');
    }
};
