<?php

namespace App\Filament\Immobilisation\Widgets;

use Filament\Widgets\ChartWidget;

class DepreciationForecastChart extends ChartWidget
{
    protected ?string $heading = 'Prévision des dotations (5 prochaines années)';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $currentYear = now()->year;
        $years = [];
        $amounts = [];

        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear + $i;
            $years[] = (string) $year;

            // Sum of depreciations planned for this year
            $sum = \App\Models\Immobilisation\Depreciation::whereYear('period_date', $year)
                ->sum('amount');

            $amounts[] = round($sum, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Dotations aux amortissements (€)',
                    'data' => $amounts,
                    'backgroundColor' => '#f59e0b',
                ],
            ],
            'labels' => $years,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
