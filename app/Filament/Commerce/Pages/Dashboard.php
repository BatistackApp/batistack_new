<?php

namespace App\Filament\Commerce\Pages;

use App\Filament\Commerce\Widgets\MonthlyOrderVolumeChart;
use App\Filament\Commerce\Widgets\MonthlyRevenueVarianceWidget;
use App\Filament\Commerce\Widgets\OverdueInvoicesDetailWidget;
use App\Filament\Commerce\Widgets\PurchasesStatsWidget;
use App\Filament\Commerce\Widgets\RevenueGoalProgressWidget;
use App\Filament\Commerce\Widgets\SalesPipelineFunnelWidget;
use App\Filament\Commerce\Widgets\TopCustomersWidget;
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
            MonthlyRevenueVarianceWidget::class,
            RevenueGoalProgressWidget::class,
            SalesPipelineFunnelWidget::class,
            OverdueInvoicesDetailWidget::class,
            MonthlyOrderVolumeChart::class,
            PurchasesStatsWidget::class,
            TopCustomersWidget::class,
        ];
    }
}
