<?php

namespace App\Filament\Articles\Pages;

use App\Filament\Articles\Widgets\ArticlesStatsOverview;
use App\Filament\Articles\Widgets\LowStockTableWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{

    protected static ?string $title = 'Tableau de bord Logistique';

    public function getColumns(): int | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            ArticlesStatsOverview::class,
            \App\Filament\Articles\Widgets\StockMovementsChart::class,
            \App\Filament\Articles\Widgets\WarehouseDistributionChart::class,
            \App\Filament\Articles\Widgets\LatestStockMouvementsWidget::class,
            \App\Filament\Articles\Widgets\ExpectedDeliveriesWidget::class,
            LowStockTableWidget::class,
        ];
    }
}
