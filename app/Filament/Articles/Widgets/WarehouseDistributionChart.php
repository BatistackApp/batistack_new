<?php

namespace App\Filament\Articles\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class WarehouseDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Répartition par Entrepôt (Valeur)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $distribution = DB::table('stocks')
            ->join('items', 'stocks.item_id', '=', 'items.id')
            ->join('warehouses', 'stocks.warehouse_id', '=', 'warehouses.id')
            ->select('warehouses.name', DB::raw('SUM(stocks.quantity * items.purchase_price) as total_value'))
            ->groupBy('warehouses.name')
            ->get();

        $labels = $distribution->pluck('name')->toArray();
        $values = $distribution->pluck('total_value')->toArray();

        // Générer des couleurs dynamiques (ou fixes) pour le graphe
        $colors = [
            '#3b82f6', // blue
            '#f59e0b', // amber
            '#10b981', // emerald
            '#8b5cf6', // violet
            '#ef4444', // red
            '#14b8a6', // teal
            '#f43f5e', // rose
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Valeur du Stock (€)',
                    'data' => $values,
                    'backgroundColor' => array_slice($colors, 0, count($values)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
