<?php

namespace App\Filament\RH\Widgets;

use App\Services\RH\SafetyRateService;
use Filament\Widgets\ChartWidget;

class SafetyRatesChart extends ChartWidget
{
    protected ?string $heading = 'Évolution mensuelle TF / TG (12 derniers mois)';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $series = app(SafetyRateService::class)->monthlySeries();

        return [
            'datasets' => [
                [
                    'label' => 'TF',
                    'data' => array_column($series, 'tf'),
                    'backgroundColor' => '#ef4444',
                    'borderColor' => '#ef4444',
                    'type' => 'line',
                ],
                [
                    'label' => 'TG',
                    'data' => array_column($series, 'tg'),
                    'backgroundColor' => '#f59e0b',
                    'borderColor' => '#f59e0b',
                    'type' => 'line',
                ],
            ],
            'labels' => array_column($series, 'month'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}