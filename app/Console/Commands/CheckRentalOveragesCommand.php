<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckRentalOveragesCommand extends Command
{
    protected $signature = 'rentals:check-overages';

    protected $description = 'Vérifie les dépassements de location et notifie les chefs de chantier';

    public function handle()
    {
        $tomorrow = \Carbon\Carbon::tomorrow()->toDateString();
        $today = \Carbon\Carbon::today()->toDateString();

        // J-1: Alert for tomorrow
        $contractsEndingTomorrow = \App\Models\Locations\RentalContract::with('chantier')
            ->where('status', \App\Enums\Locations\RentalStatus::ACTIVE)
            ->whereDate('expected_end_date', $tomorrow)
            ->get();

        foreach ($contractsEndingTomorrow as $contract) {
            $this->info("Alerte J-1 pour le contrat {$contract->reference}");
            // Envoyer une notification au chef de chantier (si le système de notification est en place)
        }

        // J+X: Overdue penalty alerts
        $overdueContracts = \App\Models\Locations\RentalContract::with('chantier')
            ->where('status', \App\Enums\Locations\RentalStatus::ACTIVE)
            ->whereDate('expected_end_date', '<', $today)
            ->whereNotNull('daily_penalty_rate')
            ->get();

        foreach ($overdueContracts as $contract) {
            $this->warn("Dépassement détecté pour le contrat {$contract->reference}, application des pénalités.");
            // Envoyer une notification de pénalité financière
        }
    }
}
