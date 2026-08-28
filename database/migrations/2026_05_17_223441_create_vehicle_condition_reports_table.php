<?php

use App\Models\Flottes\VehicleAssignment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_condition_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(VehicleAssignment::class)->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('odometer', 12);
            $table->integer('fuel_level');
            $table->string('signature_checksum');
            $table->dateTime('signed_at');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_condition_reports');
    }
};
