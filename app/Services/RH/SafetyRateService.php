<?php

namespace App\Services\RH;

use App\Enums\RH\AbsenceType;
use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\Abscence;
use App\Models\RH\TimeEntry;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class SafetyRateService
{
    /**
     * Calcule le Taux de Fréquence (TF) et le Taux de Gravité (TG) des accidents
     * du travail sur les 12 derniers mois (période glissante, temps réel).
     */
    public function rollingYear(): array
    {
        $to = now()->endOfDay();
        $from = $to->copy()->subYear()->addDay()->startOfDay();

        return $this->compute($from, $to);
    }

    /**
     * Calcule TF / TG entre deux dates.
     *
     * @return array{from: CarbonInterface, to: CarbonInterface, hoursWorked: float, accidentCount: int, daysLost: int, tf: float, tg: float}
     */
    public function compute(CarbonInterface $from, CarbonInterface $to): array
    {
        $hoursWorked = (float) TimeEntry::query()
            ->whereBetween('date', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereIn('status', [TimeEntryStatus::APPROVED, TimeEntryStatus::LOCKED])
            ->sum('hours');

        $accidents = Abscence::query()
            ->where('type', AbsenceType::WORK_ACCIDENT)
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from)
            ->get();

        $accidentCount = $accidents->count();
        $daysLost = $accidents->sum(fn (Abscence $a) => $this->daysLostInRange($a, $from, $to));

        $tf = $hoursWorked > 0 ? round(($accidentCount * 1_000_000) / $hoursWorked, 2) : 0.0;
        $tg = $hoursWorked > 0 ? round(($daysLost * 1_000) / $hoursWorked, 2) : 0.0;

        return [
            'from' => $from->copy()->startOfDay(),
            'to' => $to->copy()->endOfDay(),
            'hoursWorked' => $hoursWorked,
            'accidentCount' => $accidentCount,
            'daysLost' => $daysLost,
            'tf' => $tf,
            'tg' => $tg,
        ];
    }

    /**
     * Nombre de jours calendaires d'arrêt (bornes incluses).
     */
    public function daysLost(Abscence $absence): int
    {
        $start = $absence->start_date;
        $end = $absence->end_date;

        if (!$start || !$end) {
            return 0;
        }

        return max(0, $start->diffInDays($end) + 1);
    }

    /**
     * Nombre de jours d'arrêt compris dans l'intersection de l'arrêt avec la
     * période demandée [$from, $to] (bornes incluses).
     */
    public function daysLostInRange(Abscence $absence, CarbonInterface $from, CarbonInterface $to): int
    {
        $start = $absence->start_date;
        $end = $absence->end_date;

        if (!$start || !$end) {
            return 0;
        }

        $overlapStart = $start->max($from);
        $overlapEnd = $end->min($to);

        if ($overlapEnd->lt($overlapStart)) {
            return 0;
        }

        return $overlapStart->diffInDays($overlapEnd) + 1;
    }

    /**
     * Séries mensuelles TF/TG pour le graphique (de -$months à aujourd'hui).
     */
    public function monthlySeries(int $months = 12): array
    {
        $series = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->startOfMonth()->subMonths($i);
            $result = $this->compute($month->copy()->startOfMonth(), $month->copy()->endOfMonth());

            $series[] = [
                'month' => $month->translatedFormat('M Y'),
                'tf' => $result['tf'],
                'tg' => $result['tg'],
            ];
        }

        return $series;
    }
}