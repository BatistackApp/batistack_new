<?php

namespace App\Filament\Widgets\Gpao;

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Gpao\ManufacturingOrder;
use App\Models\Gpao\QualityCheck;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GpaoOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $inProgress = ManufacturingOrder::where('status', ManufacturingStatus::IN_PROGRESS)->count();
        $planned = ManufacturingOrder::where('status', ManufacturingStatus::PLANNED)->count();

        $totalChecks = QualityCheck::count();
        $passedChecks = QualityCheck::where('status', 'passed')->count();

        $qualityRate = $totalChecks > 0 ? round(($passedChecks / $totalChecks) * 100, 2) : null;

        $qualityStat = Stat::make('Taux de Qualité', $qualityRate !== null ? $qualityRate . '%' : '—')
            ->description('Sur l\'ensemble des contrôles');

        if ($qualityRate !== null) {
            $qualityStat->descriptionIcon($qualityRate >= 95 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                        ->color($qualityRate >= 95 ? 'success' : 'danger');
        } else {
            $qualityStat->descriptionIcon('heroicon-m-minus')
                        ->color('gray');
        }

        return [
            Stat::make('OF En Cours', $inProgress)
                ->description('Sur la ligne de production')
                ->descriptionIcon('heroicon-m-play')
                ->color('warning'),

            Stat::make('OF Planifiés', $planned)
                ->description('En attente de démarrage')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            $qualityStat,
        ];
    }
}
