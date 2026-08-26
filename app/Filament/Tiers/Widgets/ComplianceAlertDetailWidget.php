<?php

namespace App\Filament\Tiers\Widgets;

use App\Filament\Tiers\Resources\ThirdParties\ThirdPartyResource;
use App\Models\Tiers\ThirdParty;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;

class ComplianceAlertDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Alertes Conformité Légale (Sous-traitants)';
    }

    protected function getDetails(): array
    {
        $details = [];

        // Sous-traitants marqués comme non-conformes
        $nonCompliantSubcontractors = ThirdParty::subcontractors()
            ->active()
            ->nonCompliant()
            ->get();

        foreach ($nonCompliantSubcontractors as $tp) {
            $details[] = Detail::make($tp->name ?? $tp->legal_name, 'Dossier incomplet')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url(ThirdPartyResource::getUrl('edit', ['record' => $tp]));
        }

        return array_slice($details, 0, 10);
    }
}
