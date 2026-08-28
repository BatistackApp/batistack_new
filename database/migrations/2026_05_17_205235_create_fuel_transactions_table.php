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
        Schema::create('fuel_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vehicle::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->nullable()->constrained()->nullOnDelete();
            $table->decimal('liters');
            $table->decimal('cost_ht', 10);
            $table->decimal('odometer', 12);
            $table->dateTime('purchased_at');
            $table->string('station_name')->nullable();
            $table->boolean('is_suspicious')->default(false);
            $table->string('suspicion_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fuel_transactions');
    }
};
