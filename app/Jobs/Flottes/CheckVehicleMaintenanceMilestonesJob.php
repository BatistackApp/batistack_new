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
        // On récupère les véhicules actifs
        $vehicles = Vehicle::where('status', '!=', VehicleStatus::BROKEN)->get();
        $alertCount = 0;

        foreach ($vehicles as $vehicle) {
            // Seuil de révision constructeur standard fixé à 20 000 km
            if ($alertService->needsMaintenance($vehicle, 20000.00)) {
                $alertCount++;

                $managers = User::admin();
                Notification::send($managers, new MilestoneMaintenanceNotification($vehicle));
            }
        }

        Log::info("Scan kilométrique Flotte : {$alertCount} véhicule(s) nécessitent une révision préventive.");
    }
}
