<?php

namespace App\Filament\Banque\Widgets;

use App\Models\Banque\BankTransaction;
use Carbon\Carbon;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\ComparisonChartWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\ChartSeries;

class CashFlowComparisonWidget extends ComparisonChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Flux de Trésorerie (6 derniers mois)';
    }

    protected function getLabels(): array
    {
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $labels[] = now()->subMonthsNoOverflow($i)->format('m/Y');
        }
        return $labels;
    }

    protected function getSeries(): array
    {
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $months->push(now()->subMonthsNoOverflow($i)->format('Y-m'));
        }

        $startDate = now()->subMonthsNoOverflow(5)->startOfMonth();
        $endDate = now()->endOfMonth();

        // Use standard grouping for portability
        $transactions = BankTransaction::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy(function($item) {
                return Carbon::parse($item->date)->format('Y-m');
            });

        $incomes = [];
        $expenses = [];

        foreach ($months as $month) {
            $monthTx = $transactions->get($month, collect());
            
            $monthIncome = $monthTx->where('type.value', 'credit')->sum('amount');
            $monthExpense = $monthTx->where('type.value', 'debit')->sum('amount');
            
            $incomes[] = (float) $monthIncome;
            $expenses[] = (float) abs($monthExpense);
        }

        return [
            ChartSeries::make('Entrées (Crédits)')
                ->values($incomes)
                ->type('bar')
                ->color('success'),
                
            ChartSeries::make('Sorties (Débits)')
                ->values($expenses)
                ->type('bar')
                ->color('danger'),
        ];
    }
}
