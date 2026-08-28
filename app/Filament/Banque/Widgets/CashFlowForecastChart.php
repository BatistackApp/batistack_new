<?php

namespace App\Filament\Banque\Widgets;

use App\Services\Banque\CashFlowForecastService;
use Filament\Widgets\ChartWidget;

class CashFlowForecastChart extends ChartWidget
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'Prévisionnel de Trésorerie (30 prochains jours)';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $service = new CashFlowForecastService;
        $forecast = $service->getForecast(30);

        return [
            'datasets' => [
                [
                    'label' => 'Solde Confirmé (Factures seules)',
                    'data' => $forecast['balances_confirmed'],
                    'borderColor' => '#475569',
                    'backgroundColor' => 'rgba(71, 85, 105, 0.1)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                ],
                [
                    'label' => 'Solde Prévisionnel (Avec Devis)',
                    'data' => $forecast['balances_optimistic'],
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'fill' => true,
                ],
            ],
            'labels' => $forecast['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
