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
        $lowStocks = Stock::with(['item', 'warehouse', 'locations'])
            ->whereColumn('quantity', '<=', 'min_threshold')
            ->get();

        return $lowStocks->map(function ($stock) {
            $locations = $stock->locations->pluck('location_code')->filter()->implode(', ');
            $subtitle = 'Stock: '.$stock->quantity.' (Alerte: '.$stock->min_threshold.')';

            if ($locations !== '') {
                $subtitle .= " — Bins: {$locations}";
            }

            return Detail::make($stock->item?->name ?? 'Article inconnu', $stock->quantity)
                ->badge($subtitle)
                ->badgeColor('danger')
                ->icon('heroicon-o-exclamation-triangle')
                ->url($stock->item_id ? route('filament.articles.resources.items.edit', ['record' => $stock->item_id]) : '#');
        })->toArray();
    }
}
