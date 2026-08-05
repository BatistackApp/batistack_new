<?php

namespace App\Filament\Articles\Widgets;

use App\Models\Articles\Stock;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use Illuminate\Support\Collection;

class LowStockAlertWidget extends DetailListWidget
{
    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return 'Alertes de Stock Bas';
    }

    protected function getDetails(): array
    {
        $lowStocks = Stock::with(['item', 'warehouse'])
            ->whereColumn('quantity', '<=', 'min_threshold')
            ->get();
            
        return $lowStocks->map(function ($stock) {
            return Detail::make($stock->item?->name ?? 'Article inconnu', $stock->quantity)
                ->badge('Stock: ' . $stock->quantity . ' (Alerte: ' . $stock->min_threshold . ')')
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-triangle')
                ->url(route('filament.articles.resources.stocks.edit', ['record' => $stock->id]));
        })->toArray();
    }
}
