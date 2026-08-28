<?php

use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vehicle::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Chantier::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();

            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();

            $table->decimal('start_odometer', 12, 2)->default(0);
            $table->decimal('end_odometer', 12, 2)->nullable();

            $table->string('status')->default('active');
            $table->text('purpose')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_assignments');
    }
};
