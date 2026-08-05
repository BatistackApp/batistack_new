<?php

namespace App\Filament\Chantier\Widgets;

use App\Enums\Chantiers\ChantierStatus;
use App\Models\Chantiers\Chantier;
use App\Services\Chantiers\ChantierAnalyticService;
use Filament\Widgets\ChartWidget;

class BudgetConsumptionChart extends ChartWidget
{
    protected ?string $heading = 'Analyse de Consommation Heures (Top 5 Chantiers Actifs)';

    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';
    protected ?string $maxHeight = '350px';

    protected function getData(): array
    {
        $analyticService = app(ChantierAnalyticService::class);

        $chantiers = Chantier::where('status', ChantierStatus::IN_PROGRESS)
            ->orderByDesc('budget_hours')
            ->take(5)
            ->get();

        $labels = $chantiers->pluck('reference')->toArray();
        $budgetData = [];
        $realData = [];

        foreach ($chantiers as $chantier) {
            $metrics = $analyticService->getPerformanceMetrics($chantier);
            $budgetData[] = $metrics['hours']['budget'];
            $realData[] = $metrics['hours']['real'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Budget Prévu (h)',
                    'data' => $budgetData,
                    'backgroundColor' => '#94a3b8', // Slate 400
                ],
                [
                    'label' => 'Réel Consommé (h)',
                    'data' => $realData,
                    'backgroundColor' => '#f59e0b', // Orange 500
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
