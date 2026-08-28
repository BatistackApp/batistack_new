<?php

namespace App\Filament\Salarie\Widgets;

use App\Enums\RH\TimeEntryStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\RH\TimeEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MonthlyHoursRecapWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected ?string $heading = 'Heures travaillées (3 derniers mois)';

    protected ?string $description = 'Répartition par type d\'heure';

    protected ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $employee = Auth::user()?->salarie;

        if (! $employee) {
            return ['labels' => [], 'datasets' => []];
        }

        $months = collect();
        for ($i = 2; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months->push([
                'label' => $date->translatedFormat('M Y'),
                'year' => $date->year,
                'month' => $date->month,
            ]);
        }

        $labels = $months->pluck('label')->toArray();

        $datasets = [];

        $colors = [
            TimeEntryType::NORMAL->value => '#3b82f6',
            TimeEntryType::OVERTIME_25->value => '#f59e0b',
            TimeEntryType::OVERTIME_50->value => '#ef4444',
            TimeEntryType::NIGHT->value => '#6366f1',
            TimeEntryType::SUNDAY->value => '#10b981',
        ];

        foreach (TimeEntryType::cases() as $type) {
            $data = [];

            foreach ($months as $month) {
                $hours = TimeEntry::where('employee_id', $employee->id)
                    ->where('status', TimeEntryStatus::APPROVED)
                    ->whereYear('date', $month['year'])
                    ->whereMonth('date', $month['month'])
                    ->where('type', $type)
                    ->sum('hours');

                $data[] = (float) $hours;
            }

            $hasData = array_sum($data) > 0;

            $datasets[] = [
                'label' => $type->getLabel(),
                'data' => $data,
                'backgroundColor' => $colors[$type->value] ?? '#6b7280',
                'borderRadius' => 4,
                'hidden' => ! $hasData,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): ?array
    {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
