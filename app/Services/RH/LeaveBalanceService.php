<?php

namespace App\Services\RH;

use App\Enums\RH\AbsenceType;
use App\Models\RH\Abscence;
use App\Models\RH\Employee;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveBalanceService
{
    /**
     * Calcule le solde restant pour un type de congé spécifique.
     */
    public function getBalance(Employee $employee, AbsenceType $type = AbsenceType::PAID_LEAVE): float
    {
        $acquired = $this->getAcquiredRights($employee, $type);
        $consumed = $this->getConsumedDays($employee, $type);

        return round($acquired - $consumed, 2);
    }

    /**
     * Calcule les droits acquis au pro-rata (2.5 jours / mois pour les CP).
     */
    public function getAcquiredRights(Employee $employee, AbsenceType $type): float
    {
        $contract = $employee->currentContract;
        if (! $contract) {
            return 0;
        }

        $startDate = $contract->start_date;
        $now = Carbon::now();

        // On calcule le nombre de mois travaillés depuis le début de l'année de référence (souvent Juin en France)
        // Pour simplifier ici, on calcule depuis le début d'année civile
        $referenceDate = $startDate->isAfter($now->copy()->startOfYear()) ? $startDate : $now->copy()->startOfYear();
        $monthsWorked = $referenceDate->diffInMonths($now);

        $ratePerMonth = match ($type) {
            AbsenceType::PAID_LEAVE => 2.5,
            AbsenceType::RTT => 0.83, // Exemple pour 10 jours/an
            default => 0,
        };

        return $monthsWorked * $ratePerMonth;
    }

    /**
     * Calcule le nombre de jours consommés en excluant les week-ends.
     */
    public function getConsumedDays(Employee $employee, AbsenceType $type): float
    {
        $absences = Abscence::where('employee_id', $employee->id)
            ->where('type', $type)
            ->where('is_paid', true) // On ne décompte que ce qui impacte le solde payé
            ->get();

        $totalDays = 0;

        foreach ($absences as $absence) {
            $period = CarbonPeriod::create($absence->start_date, $absence->end_date);

            foreach ($period as $date) {
                // On n'ajoute que les jours ouvrés (Lundi-Vendredi)
                // Note : On pourrait croiser avec un calendrier de jours fériés BTP
                if (! $date->isWeekend()) {
                    $totalDays += 1;
                }
            }
        }

        return (float) $totalDays;
    }
}
