<?php

namespace App\Jobs\Flottes;

use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use App\Models\User;
use App\Notifications\Flottes\MilestoneMaintenanceNotification;
use App\Services\Flottes\VehicleAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Log;

class CheckVehicleMaintenanceMilestonesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(VehicleAlertService $alertService): void
    {
        $vehicles = Vehicle::where('status', '!=', VehicleStatus::BROKEN)->get();
        $alertCount = 0;
        $managers = User::where('is_admin', true)->get();

        foreach ($vehicles as $vehicle) {
            // Révision tous les 20 000 km
            if ($alertService->needsMaintenance($vehicle, 20000.00)) {
                $alertCount++;
                $kmLeft = $alertService->getKilometersUntilMaintenance($vehicle, 20000.00);

                Notification::send($managers, new MilestoneMaintenanceNotification($vehicle, $kmLeft));

                Log::info("Maintenance due : {$vehicle->reference} - {$kmLeft} km avant révision");
            }

            // Alerte si dans les 2000 km de la révision
            $kmLeft = $alertService->getKilometersUntilMaintenance($vehicle, 20000.00);
            if ($kmLeft > 0 && $kmLeft <= 2000) {
                Log::warning("Maintenance imminente : {$vehicle->reference} - {$kmLeft} km");
            }
        }

        Log::info("Scan kilométrique : {$alertCount} véhicule(s) nécessitent révision");
    }
}
