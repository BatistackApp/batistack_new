<?php

namespace App\Filament\Chantier\Widgets;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;
use Filament\Widgets\Widget;

class GpsTrackingMapWidget extends Widget
{
    protected string $view = 'filament.chantier.widgets.gps-tracking-map-widget';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    /**
     * Récupère les dernières positions GPS des interventions en cours.
     */
    public function getActiveTracks(): array
    {
        return Intervention::query()
            ->whereIn('status', [InterventionStatus::EN_COURS, InterventionStatus::PLANIFIEE])
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->with([
                'chantier:id,name,reference',
                'latestGpsTrack.employee:id,first_name,last_name',
            ])
            ->get()
            ->map(fn ($intervention) => [
                'id' => $intervention->id,
                'reference' => $intervention->reference,
                'chantier_name' => $intervention->chantier?->name ?? 'Sans chantier',
                'technicien_name' => $intervention->latestGpsTrack?->employee
                    ? $intervention->latestGpsTrack->employee->first_name.' '.$intervention->latestGpsTrack->employee->last_name
                    : 'Inconnu',
                'lat' => (float) $intervention->last_latitude,
                'lng' => (float) $intervention->last_longitude,
                'recorded_at' => $intervention->last_gps_at?->format('d/m/Y H:i') ?? 'N/A',
                'status' => $intervention->status->value,
                'status_label' => $intervention->status->getLabel(),
                'status_color' => $intervention->status->getColor(),
                'url' => "/chantier/interventions/{$intervention->id}",
            ])
            ->toArray();
    }
}
