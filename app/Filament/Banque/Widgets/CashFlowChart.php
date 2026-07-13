<?php

namespace App\Filament\Banque\Widgets;

use App\Models\Banque\BankTransaction;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class CashFlowChart extends ChartWidget
{
    protected ?string $heading = 'Flux de Trésorerie (30 derniers jours)';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $days->push(now()->subDays($i)->format('Y-m-d'));
        }

        $transactions = BankTransaction::where('date', '>=', now()->subDays(30))
            ->get()
            ->groupBy(fn ($t) => Carbon::parse($t->date)->format('Y-m-d'));

        $credits = [];
        $debits = [];

        foreach ($days as $day) {
            $dailyTransactions = $transactions->get($day, collect());
            $credits[] = $dailyTransactions->where('amount', '>', 0)->sum('amount');
            $debits[] = abs($dailyTransactions->where('amount', '<', 0)->sum('amount'));
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entrées (Crédits)',
                    'data' => $credits,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                ],
                [
                    'label' => 'Sorties (Débits)',
                    'data' => $debits,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                ],
            ],
            'labels' => $days->map(fn ($d) => Carbon::parse($d)->format('d/m'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
