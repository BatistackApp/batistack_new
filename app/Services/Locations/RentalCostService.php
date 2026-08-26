<?php

namespace App\Services\Locations;

use App\Models\Locations\RentalContract;
use Illuminate\Support\Carbon;

class RentalCostService
{
    /**
     * Calcule le nombre de jours oÃ¹ la location a Ã©tÃ© effective jusqu'Ã  aujourd'hui (ou jusqu'Ã  la fin du contrat).
     */
    public function getActiveDays(RentalContract $contract, ?Carbon $upToDate = null): int
    {
        $start = $contract->start_date;
        if (! $start) {
            return 0;
        }

        $end = $upToDate ?? today();

        // Si le contrat est terminÃ©, la date de fin effective est la date de fin du contrat (si elle est avant $end)
        if ($contract->end_date && $contract->end_date->isBefore($end)) {
            $end = $contract->end_date;
        }

        if ($start->isAfter($end)) {
            return 0;
        }

        // +1 car si on loue du 1er au 1er, Ã§a compte 1 jour.
        return $start->diffInDays($end) + 1;
    }

    /**
     * Calcule le nombre de jours de pénalité de retard.
     */
    public function getPenaltyDays(RentalContract $contract, ?Carbon $upToDate = null): int
    {
        if (! $contract->expected_end_date || ! $contract->daily_penalty_rate) {
            return 0;
        }

        $end = $upToDate ?? today();

        if ($contract->end_date && $contract->end_date->isBefore($end)) {
            $end = $contract->end_date;
        }

        if ($contract->expected_end_date->isAfter($end) || $contract->expected_end_date->isSameDay($end)) {
            return 0;
        }

        return $contract->expected_end_date->diffInDays($end);
    }

    /**
     * Calcule le coût cumulé du contrat incluant les pénalités.
     */
    public function getCumulativeCost(RentalContract $contract, ?Carbon $upToDate = null): float
    {
        $days = $this->getActiveDays($contract, $upToDate);
        $baseCost = $days * (float) $contract->daily_cost_ht;

        $penaltyDays = $this->getPenaltyDays($contract, $upToDate);
        $penaltyCost = $penaltyDays * (float) $contract->daily_penalty_rate;

        return $baseCost + $penaltyCost;
    }
}
