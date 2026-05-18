<?php

namespace App\Jobs\Flottes;

use App\Enums\Flottes\VehicleType;
use App\Models\Flottes\Vehicle;
use App\Models\User;
use App\Notifications\Flottes\ContractExpiringNotification;
use App\Notifications\Flottes\VulPollutionControlAlertNotification;
use App\Services\Flottes\VehicleAlertService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Log;

class CheckExpiringContractsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(VehicleAlertService $alertService): void
    {
        $managers = User::all(); // À filtrer par rôle 'fleet_manager'

        // 1. Scan des contrats arrivant à échéance sous 30 jours
        $expiringContracts = $alertService->getExpiringContracts(30);
        foreach ($expiringContracts as $contract) {
            Notification::send($managers, new ContractExpiringNotification($contract));
        }

        // 2. Scan réglementaire des Utilitaires (VUL) - Contrôle pollution annuel à J-30
        $vulAlerts = Vehicle::query()
            ->where('type', VehicleType::UTILITY)
            ->where('pollution_control_due_at', '<=', now()->addDays(30))
            ->where('pollution_control_due_at', '>', now())
            ->get();

        foreach ($vulAlerts as $vehicle) {
            Notification::send($managers, new VulPollutionControlAlertNotification($vehicle));
            Log::info("Alerte VUL Pollution : Le véhicule utilitaire {$vehicle->license_plate} a une visite pollution planifiée sous 30 jours.");
        }

        Log::info('Scan de conformité administrative Flotte terminé : '.($expiringContracts->count() + $vulAlerts->count()).' alertes générées.');
    }
}
