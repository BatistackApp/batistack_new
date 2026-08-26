<?php

namespace App\Filament\Articles\Pages;

use App\Filament\Articles\Widgets\ArticlesStatsOverview;
use App\Filament\Articles\Widgets\ExpectedDeliveriesWidget;
use App\Filament\Articles\Widgets\InventoryValueVarianceWidget;
use App\Filament\Articles\Widgets\LatestStockMouvementsWidget;
use App\Filament\Articles\Widgets\LowStockAlertWidget;
use App\Filament\Articles\Widgets\LowStockTableWidget;
use App\Filament\Articles\Widgets\StockCompositionWidget;
use App\Filament\Articles\Widgets\StockMovementsChart;
use App\Filament\Articles\Widgets\StockRotationTrendWidget;
use App\Filament\Articles\Widgets\WarehouseDistributionChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord Logistique';

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            ArticlesStatsOverview::class,
            InventoryValueVarianceWidget::class,
            StockRotationTrendWidget::class,
            LowStockAlertWidget::class,
            StockMovementsChart::class,
            StockCompositionWidget::class,
            WarehouseDistributionChart::class,
            LatestStockMouvementsWidget::class,
            ExpectedDeliveriesWidget::class,
            LowStockTableWidget::class,
        ];
    }
}
