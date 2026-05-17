<?php

namespace App\Observers\Flottes;

use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Flottes\RecalculateVehicleTcoJob;
use App\Models\Flottes\VehicleMaintenance;

class VehicleMaintenanceObserver
{
    public function saved(VehicleMaintenance $maintenance): void
    {
        $vehicle = $maintenance->vehicle;

        // 1. Si la maintenance s'est faite à un kilométrage supérieur, on met à jour le véhicule
        if ($maintenance->odometer_at_maintenance && $maintenance->odometer_at_maintenance > $vehicle->odometer) {
            $vehicle->updateQuietly([
                'odometer' => $maintenance->odometer_at_maintenance,
            ]);
        }

        // 2. Si le type de maintenance indique une immobilisation, on adapte le statut
        if (in_array(strtolower($maintenance->type), ['panne', 'accident', 'grosse réparation'])) {
            $vehicle->update([
                'status' => VehicleStatus::BROKEN,
            ]);
        }

        // 3. Lancement d'un Job asynchrone pour recalculer le TCO (Total Cost of Ownership)
        RecalculateVehicleTcoJob::dispatch($vehicle);
    }
}
