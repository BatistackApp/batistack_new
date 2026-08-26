<?php

namespace App\Filament\Gpao\Widgets;

use App\Enums\Gpao\ManufacturingStatus;
use App\Models\Gpao\ManufacturingOrder;
use Filament\Widgets\ChartWidget;

class ProductionChart extends ChartWidget
{
    protected ?string $heading = 'Production (OF Terminés)';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getFilters(): ?array
    {
        return [
            'month' => '30 derniers jours',
            'semester' => '6 derniers mois',
        ];
    }

    protected function getData(): array
    {
        $activeFilter = $this->filter ?? 'semester';

        $data = [];
        $labels = [];

        if ($activeFilter === 'month') {
            // Vue 30 jours, on groupe par jour
            $start = now()->subDays(30)->startOfDay();
            $end = now()->endOfDay();

            $ofs = ManufacturingOrder::where('status', ManufacturingStatus::COMPLETED)
                ->whereBetween('end_date', [$start, $end])
                ->selectRaw('DATE(end_date) as date, COUNT(*) as count')
                ->groupBy('date')
                ->pluck('count', 'date');

            // Remplir les jours vides
            for ($i = 0; $i < 30; $i++) {
                $date = now()->subDays(29 - $i)->format('Y-m-d');
                $labels[] = now()->subDays(29 - $i)->format('d M');
                $data[] = $ofs[$date] ?? 0;
            }
        } else {
            // Vue 6 mois, on groupe par mois
            $start = now()->subMonths(5)->startOfMonth();
            $end = now()->endOfMonth();

            $ofs = ManufacturingOrder::where('status', ManufacturingStatus::COMPLETED)
                ->whereBetween('end_date', [$start, $end])
                ->selectRaw('DATE_FORMAT(end_date, "%Y-%m") as month, COUNT(*) as count')
                ->groupBy('month')
                ->pluck('count', 'month');

            for ($i = 0; $i < 6; $i++) {
                $dateObj = now()->subMonths(5 - $i);
                $monthKey = $dateObj->format('Y-m');
                $labels[] = $dateObj->translatedFormat('F'); // Mois en français (si locale FR)
                $data[] = $ofs[$monthKey] ?? 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'OF Terminés',
                    'data' => $data,
                    'backgroundColor' => '#f59e0b', // Amber 500 pour coller au primary GPAO
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
