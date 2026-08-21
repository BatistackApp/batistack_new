<?php

namespace App\Jobs\Locations;

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Notifications\RentalExpirationAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RentalExpirationAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiringContracts = RentalContract::query()
            ->where('status', RentalStatus::ACTIVE)
            ->whereNotNull('end_date')
            ->whereDate('end_date', today()->addDays(3))
            ->with(['chantier.manager.user'])
            ->get();

        foreach ($expiringContracts as $contract) {
            $managerUser = $contract->chantier?->manager?->user;

            if ($managerUser) {
                try {
                    $managerUser->notify(new RentalExpirationAlert($contract, 3));
                    Log::info("Notification échéance J-3 envoyée pour le contrat {$contract->reference} au manager {$managerUser->email}");
                } catch (\Exception $e) {
                    Log::error("Échec notification échéance J-3 pour le contrat {$contract->reference}: {$e->getMessage()}");
                }
            } else {
                Log::warning("Aucun manager/user pour le chantier du contrat {$contract->reference} (échéance J-3)");
            }
        }
    }
}
