<?php

namespace App\Filament\Terrain\Pages;

use App\Filament\Terrain\Widgets\DailyActivityWidget;
use App\Filament\Terrain\Widgets\TeamComplianceWidget;
use App\Filament\Terrain\Widgets\TerrainDashboardWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class TerrainDashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            TerrainDashboardWidget::class,
            TeamComplianceWidget::class,
            DailyActivityWidget::class,
        ];
    }
}
