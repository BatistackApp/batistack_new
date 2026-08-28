<?php

use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('traffic_fines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vehicle::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->dateTime('infraction_at');
            $table->decimal('amount', 10, 2);
            $table->integer('points_deducted')->default(0);
            $table->string('status')->default('received');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('traffic_fines');
    }
};
