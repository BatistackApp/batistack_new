<?php

namespace App\Filament\Articles\Widgets;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ArticlesStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // 1. Valeur totale du stock au PUMP
        $totalStockValue = DB::table('stocks')
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->select(DB::raw('SUM(stocks.quantity * items.purchase_price) as total'))
            ->value('total') ?? 0;

        // 2. Nombre d'articles en alerte de stock
        $alertsCount = Stock::whereColumn('quantity', '<=', 'min_threshold')
            ->where('quantity', '>', 0)
            ->count();

        // 3. Nombre de ruptures sèches
        $outOfStockCount = Stock::where('quantity', '<=', 0)->count();

        return [
            Stat::make('Valeur du Stock', number_format($totalStockValue, 2, ',', ' ').' €')
                ->description('Valorisation totale au PUMP')
                ->descriptionIcon(Phosphor::CurrencyEur)
                ->color('success'),

            Stat::make('Alertes de Seuil', $alertsCount)
                ->description('Articles à réapprovisionner')
                ->descriptionIcon(Phosphor::Warning)
                ->color('warning'),

            Stat::make('Ruptures de Stock', $outOfStockCount)
                ->description('Articles avec quantité nulle ou négative')
                ->descriptionIcon(Phosphor::Prohibit)
                ->color('danger'),

            Stat::make('Catalogue Ouvrages', Item::where('type', ItemType::WORK)->count())
                ->description('Recettes techniques configurées')
                ->descriptionIcon(Phosphor::Stack)
                ->color('info'),
        ];
    }
}
