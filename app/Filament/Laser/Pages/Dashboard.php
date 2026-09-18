<?php

namespace App\Filament\Laser\Pages;

use App\Filament\Laser\Widgets\LaserOverdueInvoicesWidget;
use App\Filament\Laser\Widgets\LaserPipelineFunnelWidget;
use App\Filament\Laser\Widgets\LaserRecentOrdersWidget;
use App\Filament\Laser\Widgets\LaserRevenueChart;
use App\Filament\Laser\Widgets\LaserStatsWidget;
use App\Filament\Laser\Widgets\LaserTopClientsWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 2,
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    public function getWidgets(): array
    {
        return [
            LaserStatsWidget::class,
            LaserPipelineFunnelWidget::class,
            LaserRevenueChart::class,
            LaserRecentOrdersWidget::class,
            LaserTopClientsWidget::class,
            LaserOverdueInvoicesWidget::class,
        ];
    }
}
