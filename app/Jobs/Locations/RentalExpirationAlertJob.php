<?php

namespace App\Jobs\Locations;

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RentalExpirationAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    /**
     * Parcourt les contrats de location bientÃ´t expirÃ©s et notifie les responsables.
     */
    public function handle(): void
    {
        // RÃ©cupÃ©rer les contrats actifs dont la date de fin est dans 3 jours
        $expiringContracts = RentalContract::query()
            ->where('status', RentalStatus::ACTIVE)
            ->whereNotNull('end_date')
            ->whereDate('end_date', today()->addDays(3))
            ->with(['chantier.manager'])
            ->get();

        foreach ($expiringContracts as $contract) {
            $manager = $contract->chantier?->manager;
            if ($manager) {
                // TODO: Envoi de notification au manager via Filament (DatabaseNotification) ou Email
                Log::info("Le contrat de location {$contract->reference} se termine dans 3 jours. Notification au manager {$manager->email}");
            }
        }
    }
}
