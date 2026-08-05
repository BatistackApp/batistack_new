<?php

namespace App\Filament\Interventions\Widgets;

use App\Models\Interventions\Intervention;
use App\Enums\Interventions\InterventionStatus;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\FunnelWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\FunnelStage;

class InterventionPipelineFunnelWidget extends FunnelWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;

    public function getHeading(): string
    {
        return 'Pipeline des Interventions';
    }

    protected function getStages(): array
    {
        $brouillons = Intervention::where('status', InterventionStatus::BROUILLON)->count();
        $planifiees = Intervention::where('status', InterventionStatus::PLANIFIEE)->count();
        $enCours = Intervention::where('status', InterventionStatus::EN_COURS)->count();
        $terminees = Intervention::where('status', InterventionStatus::TERMINEE)->count();
        $facturees = Intervention::where('status', InterventionStatus::FACTUREE)->count();

        // Le tunnel cumule généralement les valeurs (ceux en bas sont passés par le haut)
        // Mais si on veut juste afficher l'état du parc, on peut les passer comme des étapes.
        return [
            FunnelStage::make('Créés (Brouillon)', $brouillons + $planifiees + $enCours + $terminees + $facturees)->color('gray'),
            FunnelStage::make('Planifiés', $planifiees + $enCours + $terminees + $facturees)->color('info'),
            FunnelStage::make('En Cours', $enCours + $terminees + $facturees)->color('warning'),
            FunnelStage::make('Terminés / À Facturer', $terminees + $facturees)->color('success'),
            FunnelStage::make('Facturés (Clôturés)', $facturees)->color('primary'),
        ];
    }
}
