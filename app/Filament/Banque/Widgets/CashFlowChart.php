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

        $startDate = now()->subDays(29)->startOfDay();
        $endDate = now()->endOfDay();

        $transactions = BankTransaction::selectRaw('
                DATE(date) as day,
                SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END) as credits,
                SUM(CASE WHEN amount < 0 THEN ABS(amount) ELSE 0 END) as debits
            ')
            ->where('date', '>=', $startDate)
            ->where('date', '<=', $endDate)
            ->groupByRaw('DATE(date)')
            ->get()
            ->keyBy('day');

        $credits = [];
        $debits = [];

        foreach ($days as $day) {
            $data = $transactions->get($day);
            $credits[] = $data ? (float) $data->credits : 0.0;
            $debits[] = $data ? (float) $data->debits : 0.0;
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
