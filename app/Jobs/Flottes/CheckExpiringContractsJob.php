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
        $managers = User::where('is_admin', true)->get();

        // Scan contrats expirant dans 30 jours
        $expiringContracts = $alertService->getExpiringContracts(30);
        foreach ($expiringContracts as $contract) {
            Notification::send($managers, new ContractExpiringNotification($contract));
        }

        // Scan VUL - Contrôle pollution
        $vulAlerts = Vehicle::query()
            ->where('type', VehicleType::UTILITY)
            ->where('pollution_control_due_at', '<=', now()->addDays(30))
            ->where('pollution_control_due_at', '>', now())
            ->get();

        foreach ($vulAlerts as $vehicle) {
            Notification::send($managers, new VulPollutionControlAlertNotification($vehicle));
            Log::info("Alerte VUL : {$vehicle->reference} contrôle pollution dans 30 jours");
        }

        // Scan contrats déjà expirés
        $expiredContracts = $alertService->getExpiredContracts();
        foreach ($expiredContracts as $contract) {
            Notification::send($managers, new ContractExpiringNotification($contract));
            Log::warning("Contrat EXPIRÉ : {$contract->vehicle->reference} - {$contract->type}");
        }

        Log::info('Scan conformité : '.($expiringContracts->count() + $vulAlerts->count() + $expiredContracts->count()).' alertes');
    }
}
