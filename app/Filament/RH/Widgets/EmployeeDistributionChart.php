<?php

namespace App\Filament\RH\Widgets;

use App\Enums\RH\ContractType;
use App\Models\RH\Contract;
use DB;
use Filament\Widgets\ChartWidget;

class EmployeeDistributionChart extends ChartWidget
{
    protected ?string $heading = 'Structure de l\'Effectif';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        // On récupère les comptes groupés par type pour les derniers contrats de chaque employé
        $data = Contract::query()
            ->select('type', DB::raw('count(*) as total'))
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('contracts')
                    ->groupBy('employee_id');
            })
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'datasets' => [
                [
                    'label' => 'Nombre de salariés',
                    'data' => $data->values()->toArray(),
                    'backgroundColor' => [
                        '#10b981', // Success (CDI)
                        '#f59e0b', // Warning (CDD)
                        '#0ea5e9', // Info (Intérim)
                        '#64748b', // Gray (Apprenti)
                    ],
                ],
            ],
            'labels' => $data->keys()->map(fn ($type) => ContractType::from($type)->getLabel())->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
