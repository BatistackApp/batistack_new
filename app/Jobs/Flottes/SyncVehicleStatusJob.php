<?php

namespace App\Jobs\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class SyncVehicleStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $vehicles = Vehicle::all();

        foreach ($vehicles as $vehicle) {
            // Statut basé sur affectations actives
            $hasActiveAssignment = $vehicle->assignments()
                ->where('status', AssignmentStatus::ACTIVE)
                ->exists();

            $expectedStatus = $hasActiveAssignment
                ? VehicleStatus::ASSIGNED
                : VehicleStatus::AVAILABLE;

            // Sync si différent
            if ($vehicle->status !== $expectedStatus && $vehicle->status !== VehicleStatus::BROKEN && $vehicle->status !== VehicleStatus::MAINTENANCE) {
                $vehicle->updateQuietly(['status' => $expectedStatus]);
                Log::info("Sync statut {$vehicle->reference} : → {$expectedStatus->getLabel()}");
            }
        }

        Log::info('Synchronisation statut véhicules complétée');
    }
}
