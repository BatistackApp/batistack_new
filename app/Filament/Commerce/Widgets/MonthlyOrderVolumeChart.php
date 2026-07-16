<?php

namespace App\Filament\Commerce\Widgets;

use App\Services\Commerce\CommerceAnalyticService;
use Filament\Widgets\ChartWidget;

class MonthlyOrderVolumeChart extends ChartWidget
{
    protected ?string $heading = 'Évolution des commandes de l\'année (HT)';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $analyticService = app(CommerceAnalyticService::class);
        $monthlyData = $analyticService->getMonthlyOrderVolume();

        $labels = [];
        $data = [];

        foreach ($monthlyData as $month) {
            $labels[] = \Carbon\Carbon::parse('1 ' . $month['month'])->translatedFormat('F');
            $data[] = $month['total_ht'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Commandé (HT)',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
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
