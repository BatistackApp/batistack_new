<?php

namespace App\Filament\Articles\Resources\Store\Widgets;

use App\Services\Articles\StoreService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class StoreStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $stats = app(StoreService::class)->getStoreStats();

        return [
            Stat::make('Références Magasin', $stats['total_refs'])
                ->description('Articles en magasin')
                ->descriptionIcon(Phosphor::ShoppingBag)
                ->color('info'),

            Stat::make('Stock Bas', $stats['low_stock'])
                ->description('À réapprovisionner')
                ->descriptionIcon(Phosphor::Warning)
                ->color('warning'),

            Stat::make('Valeur du Stock', number_format($stats['stock_value'], 2, ',', ' ').' €')
                ->description('Valorisation magasin')
                ->descriptionIcon(Phosphor::CurrencyEur)
                ->color('success'),

            Stat::make('Mouvements Aujourd\'hui', $stats['today_movements'])
                ->description('Prélèvements du jour')
                ->descriptionIcon(Phosphor::ArrowsClockwise)
                ->color('primary'),
        ];
    }
}
