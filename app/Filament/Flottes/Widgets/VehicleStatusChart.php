<?php

namespace App\Filament\Flottes\Widgets;

use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use Filament\Widgets\ChartWidget;

class VehicleStatusChart extends ChartWidget
{
    protected ?string $heading = 'Disponibilité du Parc Roulant';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Agrégation des comptes par statut opérationnel
        $statuses = [
            VehicleStatus::AVAILABLE->value => Vehicle::where('status', VehicleStatus::AVAILABLE)->count(),
            VehicleStatus::ASSIGNED->value => Vehicle::where('status', VehicleStatus::ASSIGNED)->count(),
            VehicleStatus::MAINTENANCE->value => Vehicle::where('status', VehicleStatus::MAINTENANCE)->count(),
            VehicleStatus::BROKEN->value => Vehicle::where('status', VehicleStatus::BROKEN)->count(),
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Nombre d\'actifs',
                    'data' => array_values($statuses),
                    'backgroundColor' => [
                        '#10b981', // Vert (Disponible)
                        '#0ea5e9', // Bleu (Affecté)
                        '#f59e0b', // Orange (En révision)
                        '#ef4444', // Rouge (En panne)
                    ],
                ],
            ],
            'labels' => [
                'Disponibles',
                'En mission',
                'En entretien',
                'Hors-service',
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
