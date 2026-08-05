<?php

namespace App\Filament\Articles\Widgets;

use App\Models\Articles\Stock;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\CompositionWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Composition;
use LaBoiteACode\FilamentDashboardWidgets\Data\CompositionSlice;
use Illuminate\Support\Facades\Cache;

class StockCompositionWidget extends CompositionWidget
{
    protected static ?int $sort = 3;

    public function getHeading(): string
    {
        return 'Composition des Stocks par Magasin';
    }

    protected function getComposition(): Composition
    {
        $stocks = Cache::remember('dashboard_stock_composition', 300, function () {
            return Stock::with('warehouse')
                ->join('items', 'stocks.item_id', '=', 'items.id')
                ->selectRaw('stocks.warehouse_id, sum(stocks.quantity * items.purchase_price) as total_value')
                ->groupBy('stocks.warehouse_id')
                ->get();
        });
        
        $slices = $stocks->map(function ($stock) {
            return CompositionSlice::make($stock->warehouse?->name ?? 'Magasin inconnu', $stock->total_value)
                ->color($this->getRandomColor());
        })->toArray();
        
        return Composition::make('Valeur des stocks')
            ->type('doughnut')
            ->slices($slices);
    }
    
    private function getRandomColor(): string
    {
        $colors = ['primary', 'success', 'warning', 'info', 'danger'];
        return $colors[array_rand($colors)];
    }
}
