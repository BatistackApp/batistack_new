<?php

namespace App\Filament\Chantier\Widgets;

use App\Models\Chantiers\Chantier;
use App\Enums\Chantiers\ChantierStatus;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\FunnelWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\FunnelStage;
use Illuminate\Support\Facades\DB;

class ChantierPipelineFunnelWidget extends FunnelWidget
{
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return 'Pipeline des Projets';
    }

    protected function getStages(): array
    {
        $chantiers = Chantier::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            FunnelStage::make(ChantierStatus::STUDY->getLabel(), (float) $chantiers->get(ChantierStatus::STUDY->value, 0))->color(ChantierStatus::STUDY->getColor()),
            FunnelStage::make(ChantierStatus::PLANNED->getLabel(), (float) $chantiers->get(ChantierStatus::PLANNED->value, 0))->color(ChantierStatus::PLANNED->getColor()),
            FunnelStage::make(ChantierStatus::IN_PROGRESS->getLabel(), (float) $chantiers->get(ChantierStatus::IN_PROGRESS->value, 0))->color(ChantierStatus::IN_PROGRESS->getColor()),
            FunnelStage::make(ChantierStatus::AWAITING_RECEPTION->getLabel(), (float) $chantiers->get(ChantierStatus::AWAITING_RECEPTION->value, 0))->color(ChantierStatus::AWAITING_RECEPTION->getColor()),
            FunnelStage::make(ChantierStatus::FINISHED->getLabel(), (float) $chantiers->get(ChantierStatus::FINISHED->value, 0))->color(ChantierStatus::FINISHED->getColor()),
        ];
    }
}
