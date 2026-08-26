<?php

namespace App\Filament\Articles\Widgets;

use App\Models\Articles\StockMouvement;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Carbon;

class StockMovementsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Mouvements de Stock';

    public ?string $filter = '30_days';

    protected function getFilters(): ?array
    {
        return [
            '30_days' => '30 derniers jours',
            '12_months' => '12 derniers mois',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter;

        if ($activeFilter === '12_months') {
            $start = now()->subMonths(11)->startOfMonth();
            $end = now()->endOfMonth();

            $trendIn = Trend::query(StockMouvement::incoming())
                ->between(start: $start, end: $end)
                ->perMonth()
                ->sum('quantity_delta');

            $trendOut = Trend::query(StockMouvement::outgoing())
                ->between(start: $start, end: $end)
                ->perMonth()
                ->sum('quantity_delta');

            $labels = $trendIn->map(fn (TrendValue $value) => Carbon::parse($value->date)->translatedFormat('M Y'))->toArray();
        } else {
            $start = now()->subDays(29)->startOfDay();
            $end = now()->endOfDay();

            $trendIn = Trend::query(StockMouvement::incoming())
                ->between(start: $start, end: $end)
                ->perDay()
                ->sum('quantity_delta');

            $trendOut = Trend::query(StockMouvement::outgoing())
                ->between(start: $start, end: $end)
                ->perDay()
                ->sum('quantity_delta');

            $labels = $trendIn->map(fn (TrendValue $value) => Carbon::parse($value->date)->translatedFormat('d M'))->toArray();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entrées (IN)',
                    'data' => $trendIn->map(fn (TrendValue $value) => $value->aggregate)->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => 'start',
                ],
                [
                    'label' => 'Sorties (OUT)',
                    'data' => $trendOut->map(fn (TrendValue $value) => $value->aggregate)->toArray(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'fill' => 'start',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
