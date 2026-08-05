<?php

namespace App\Filament\Interventions\Widgets;

use App\Models\Interventions\Intervention;
use App\Enums\Interventions\InterventionStatus;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use App\Filament\Interventions\Resources\InterventionResource;

class UrgentInterventionsDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Alertes SAV (Urgences & Non assignées)';
    }

    protected function getDetails(): array
    {
        $interventions = Intervention::with('workers')
            ->whereNotIn('status', [InterventionStatus::TERMINEE, InterventionStatus::FACTUREE, InterventionStatus::ANNULEE])
            ->where(function ($query) {
                $query->doesntHave('workers')
                      ->orWhere('scheduled_at', '<=', now());
            })
            ->get();

        $details = [];

        foreach ($interventions as $intervention) {
            $issues = [];

            if ($intervention->workers->isEmpty()) {
                $issues[] = 'Non assignée';
            }

            if ($intervention->scheduled_at && $intervention->scheduled_at->isPast()) {
                $issues[] = 'Planifiée et en retard';
            } elseif ($intervention->scheduled_at && $intervention->scheduled_at->isToday()) {
                $issues[] = "Planifiée pour aujourd'hui";
            }

            if (!empty($issues)) {
                $details[] = Detail::make($intervention->reference . ' - ' . $intervention->type->getLabel(), implode(' | ', $issues))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->url(InterventionResource::getUrl('edit', ['record' => $intervention]));
            }
        }

        return $details;
    }
}
