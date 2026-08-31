<?php

namespace App\Filament\Terrain\Pages;

use App\Filament\Terrain\Widgets\ChantierProgressWidget;
use App\Filament\Terrain\Widgets\DailyActivityWidget;
use App\Filament\Terrain\Widgets\QuickActionsWidget;
use App\Filament\Terrain\Widgets\TeamComplianceWidget;
use App\Filament\Terrain\Widgets\TerrainDashboardWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class TerrainDashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
        ];
    }

    public function getWidgets(): array
    {
        return [
            QuickActionsWidget::class,
            TerrainDashboardWidget::class,
            DailyActivityWidget::class,
            TeamComplianceWidget::class,
            ChantierProgressWidget::class,
        ];
    }
}
