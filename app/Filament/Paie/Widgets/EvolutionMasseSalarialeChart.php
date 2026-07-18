<?php

namespace App\Filament\Paie\Widgets;

use App\Models\Paie\Payslip;
use Carbon\Carbon;
use DB;
use Filament\Widgets\ChartWidget;

class EvolutionMasseSalarialeChart extends ChartWidget
{
    protected ?string $heading = 'Évolution de la Masse Salariale';
    protected static ?int $sort = 2;
    protected ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->month;
            $year = $date->year;

            // Calcul du coût total employeur pour le mois
            $masseSalariale = Payslip::whereYear('payment_date', $year)
                ->whereMonth('payment_date', $month)
                ->sum(DB::raw('gross_salary + employer_cost'));

            $data[] = number_format($masseSalariale, 2, '.', '');
            $labels[] = $date->format('M Y');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Coût total employeur (€)',
                    'data' => $data,
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
