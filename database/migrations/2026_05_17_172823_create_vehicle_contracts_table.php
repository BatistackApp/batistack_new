<?php

use App\Models\Flottes\Vehicle;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vehicle::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ThirdParty::class, 'supplier_id')->constrained()->cascadeOnDelete();

            $table->string('type');
            $table->string('policy_number');

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('annual_cost_ht', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_contracts');
    }
};
