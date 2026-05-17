<?php

use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Tiers\ThirdParty;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vehicle_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vehicle::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ThirdParty::class, 'supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(VatRate::class)->constrained();
            $table->string('type');
            $table->text('description')->nullable();
            $table->decimal('cost_ht', 15, 4);
            $table->decimal('odometer_at_maintenance', 12, 2)->nullable();
            $table->date('performed_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_maintenances');
    }
};
