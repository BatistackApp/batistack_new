<?php

namespace App\Filament\Chantier\Resources\Chantiers\Widgets;

use App\Models\Chantiers\Chantier;
use Filament\Widgets\ChartWidget;

class LaborDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Répartition des heures par lot';

    public ?Chantier $record = null;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '150px';

    protected function getData(): array
    {
        $data = $this->record->phases()->with('tasks')->get()->map(function ($phase) {
            return [
                'label' => $phase->label,
                'hours' => $phase->tasks->sum('estimated_hours'),
            ];
        });

        return [
            'datasets' => [
                [
                    'label' => 'Heures prévues',
                    'data' => $data->pluck('hours')->toArray(),
                    'backgroundColor' => ['#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#6366f1'],
                ],
            ],
            'labels' => $data->pluck('label')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
