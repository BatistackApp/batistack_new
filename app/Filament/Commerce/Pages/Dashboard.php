<?php

namespace App\Filament\Commerce\Pages;

use App\Filament\Commerce\Widgets\RevenueStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            RevenueStatsWidget::class,
        ];
    }
}
