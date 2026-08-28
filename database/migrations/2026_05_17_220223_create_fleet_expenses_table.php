<?php

use App\Models\Chantiers\Chantier;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fleet_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vehicle::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Chantier::class)->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('reference')->nullable();
            $table->decimal('amount_ht', 10, 4);
            $table->foreignIdFor(VatRate::class)->constrained();
            $table->decimal('amount_ttc', 10, 2);
            $table->string('merchant_name')->nullable();
            $table->string('description')->nullable();
            $table->dateTime('spent_at');
            $table->boolean('is_suspicious')->default(false);
            $table->string('suspicion_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_expenses');
    }
};
