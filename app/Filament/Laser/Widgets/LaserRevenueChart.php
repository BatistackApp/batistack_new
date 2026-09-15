<?php

namespace App\Filament\Laser\Widgets;

use App\Enums\Laser\InvoiceStatus;
use App\Models\Laser\LaserInvoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class LaserRevenueChart extends ChartWidget
{
    protected ?string $heading = 'Chiffre d\'affaires mensuel (HT)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $year = now()->year;

        $monthlyData = LaserInvoice::whereYear('created_at', $year)
            ->whereIn('status', [InvoiceStatus::VALIDATED, InvoiceStatus::PAID])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(total_ht) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $labels = [];
        $data = [];

        for ($m = 1; $m <= 12; $m++) {
            $key = sprintf('%d-%02d', $year, $m);
            $labels[] = Carbon::parse("{$year}-{$m}-01")->translatedFormat('M');
            $data[] = (float) ($monthlyData[$key] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'CA Facturé (HT)',
                    'data' => $data,
                    'borderColor' => '#e11d48',
                    'backgroundColor' => 'rgba(225, 29, 72, 0.15)',
                    'fill' => 'start',
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
