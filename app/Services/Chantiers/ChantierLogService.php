<?php

namespace App\Services\Chantiers;

use App\Models\Chantiers\Chantier;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Service de Gestion du Journal et des Événements.
 */
class ChantierLogService
{
    /**
     * Génère une synthèse quotidienne pour le journal de chantier.
     */
    public function getDailySummary(Chantier $chantier, Carbon|CarbonImmutable $date): array
    {
        return [
            'weather' => $chantier->logs()->whereDate('date', $date)->first()?->weather_condition,
            'incidents' => $chantier->logs()->whereDate('date', $date)->where('incident_reported', true)->count(),
            'present_employees' => $chantier->timeEntries()->whereDate('date', $date)->count(),
            'notes' => $chantier->logs()->whereDate('date', $date)->pluck('content'),
        ];
    }
}
