<?php

namespace App\Jobs\Flottes;

use App\Models\User;
use App\Notifications\Flottes\ContractExpiringNotification;
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
        $expiringContracts = $alertService->getExpiringContracts(30); // Alertes à J-30

        if ($expiringContracts->isEmpty()) {
            return;
        }

        // On récupère les gestionnaires de flotte (ou administrateurs)
        $managers = User::all(); // À filtrer par rôle approprié en production

        foreach ($expiringContracts as $contract) {
            Notification::send($managers, new ContractExpiringNotification($contract));
        }

        Log::info('Scan de conformité Flotte : '.$expiringContracts->count().' contrats arrivant à échéance ont été signalés.');
    }
}
