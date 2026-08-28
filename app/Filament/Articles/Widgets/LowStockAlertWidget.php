<?php

namespace App\Filament\Articles\Widgets;

use App\Models\Articles\Stock;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;

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
                ->badge('Stock: '.$stock->quantity.' (Alerte: '.$stock->min_threshold.')')
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-triangle')
                ->url($stock->item_id ? route('filament.articles.resources.items.edit', ['record' => $stock->item_id]) : '#');
        })->toArray();
    }
}
