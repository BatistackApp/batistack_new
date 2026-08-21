<?php

namespace App\Console\Commands;

use App\Enums\Locations\RentalStatus;
use App\Models\Locations\RentalContract;
use App\Notifications\RentalExpirationAlert;
use App\Notifications\RentalOverageAlert;
use Illuminate\Console\Command;

class CheckRentalOveragesCommand extends Command
{
    protected $signature = 'rentals:check-overages';

    protected $description = 'Vérifie les dépassements de location, applique les pénalités et notifie les responsables.';

    public function handle(): int
    {
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();

        // 1. J-1 : Alerte pour contrats finissant demain (mais pas encore en dépassement)
        $endingTomorrow = RentalContract::with(['chantier.manager.user', 'supplier'])
            ->where('status', RentalStatus::ACTIVE)
            ->whereDate('end_date', $tomorrow)
            ->get();

        foreach ($endingTomorrow as $contract) {
            $this->notifyEndingSoon($contract);
        }

        // 2. J+X : Contrats en dépassement (expected_end_date < aujourd'hui)
        $overdueContracts = RentalContract::with(['chantier.manager.user', 'supplier'])
            ->whereIn('status', [RentalStatus::ACTIVE, RentalStatus::OVERDUE])
            ->whereDate('expected_end_date', '<', today())
            ->whereNotNull('daily_penalty_rate')
            ->where('daily_penalty_rate', '>', 0)
            ->get();

        foreach ($overdueContracts as $contract) {
            $this->applyOveragePenalty($contract);
        }

        return 0;
    }

    private function notifyEndingSoon(RentalContract $contract): void
    {
        $managerUser = $contract->chantier?->manager?->user;

        if ($managerUser) {
            $managerUser->notify(new RentalExpirationAlert($contract, abs($contract->end_date->diffInDays(today()))));
            $this->info("Alerte J-1 envoyée pour le contrat {$contract->reference}");
        } else {
            $this->warn("Pas de manager pour le contrat {$contract->reference} (alerte J-1)");
        }
    }

    private function applyOveragePenalty(RentalContract $contract): void
    {
        $daysOverdue = abs($contract->expected_end_date->diffInDays(today()));
        $totalPenalty = $contract->daily_penalty_rate * $daysOverdue;

        $contract->update([
            'penalty_amount' => $totalPenalty,
            'status' => RentalStatus::OVERDUE,
        ]);

        // Notifications
        $managerUser = $contract->chantier?->manager?->user;
        $supplierUser = $contract->supplier?->contact?->user;

        if ($managerUser) {
            $managerUser->notify(new RentalOverageAlert($contract, $daysOverdue, $totalPenalty, $totalPenalty));
            $this->info("Pénalité de {$totalPenalty} € appliquée pour {$contract->reference} — manager notifié");
        }

        if ($supplierUser) {
            $supplierUser->notify(new RentalOverageAlert($contract, $daysOverdue, $totalPenalty, $totalPenalty));
            $this->info("Fournisseur notifié pour {$contract->reference}");
        }
    }
}
