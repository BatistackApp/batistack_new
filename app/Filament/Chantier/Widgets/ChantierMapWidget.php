<?php

namespace App\Filament\Chantier\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use Filament\Widgets\Widget;

class ChantierMapWidget extends Widget
{
    protected string $view = 'filament.chantier.widgets.chantier-map-widget';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    /**
     * Récupère la liste des chantiers actifs disposant de coordonnées GPS valides.
     */
    public function getChantiersLocations(): array
    {
        return Chantier::query()
            ->whereIn('status', [ChantierStatus::IN_PROGRESS, ChantierStatus::PLANNED])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with('manager')
            ->get()
            ->map(fn ($chantier) => [
                'id' => $chantier->id,
                'name' => $chantier->name,
                'reference' => $chantier->reference,
                'lat' => (float) $chantier->latitude,
                'lng' => (float) $chantier->longitude,
                'address' => $chantier->full_address,
                'status_label' => $chantier->status->getLabel(),
                'status_color' => $chantier->status->getColor(),
                'manager' => $chantier->manager?->full_name ?? 'Aucun responsable',
                'url' => "/chantier/chantiers/{$chantier->id}",
            ])
            ->toArray();
    }
}
