<?php

namespace App\Filament\Gpao\Widgets;

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Gpao\ManufacturingOrder;
use LaBoiteACode\FilamentDashboardWidgets\Data\FunnelStage;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\FunnelWidget;

class ProductionFunnelWidget extends FunnelWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Tunnel de Fabrication';
    }

    protected function getStages(): array
    {
        $counts = ManufacturingOrder::select('status', \DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            FunnelStage::make('Créés', $counts[ManufacturingStatus::DRAFT->value] ?? 0)->color(ManufacturingStatus::DRAFT->getColor()),
            FunnelStage::make('Planifiés', $counts[ManufacturingStatus::PLANNED->value] ?? 0)->color(ManufacturingStatus::PLANNED->getColor()),
            FunnelStage::make('En Production', $counts[ManufacturingStatus::IN_PROGRESS->value] ?? 0)->color(ManufacturingStatus::IN_PROGRESS->getColor()),
            FunnelStage::make('Contrôle Qualité', $counts[ManufacturingStatus::QUALITY_CONTROL->value] ?? 0)->color(ManufacturingStatus::QUALITY_CONTROL->getColor()),
            FunnelStage::make('Achevés', $counts[ManufacturingStatus::COMPLETED->value] ?? 0)->color(ManufacturingStatus::COMPLETED->getColor()),
        ];
    }
}
