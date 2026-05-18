<?php

namespace App\Filament\Flottes\Widgets;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class FleetUtilizationRate extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalVehicles = Vehicle::count();
        $assignedVehicles = Vehicle::where('status', VehicleStatus::ASSIGNED)->count();

        // 1. Taux de mobilisation du parc (Actifs sur les routes)
        $mobilizationRate = $totalVehicles > 0 ? round(($assignedVehicles / $totalVehicles) * 100, 1) : 0;

        // 2. Kilomètres moyens par trajet clôturé sur le mois
        $averageDistance = (float) VehicleAssignment::where('status', AssignmentStatus::COMPLETED)
            ->whereMonth('ended_at', now()->month)
            ->whereYear('ended_at', now()->year)
            ->selectRaw('AVG(end_odometer - start_odometer) as avg_distance')
            ->value('avg_distance') ?? 0.0;

        return [
            Stat::make('Mobilisation du Parc', "{$mobilizationRate} %")
                ->description("{$assignedVehicles} véhicule(s) sur {$totalVehicles} actuellement en mission")
                ->descriptionIcon(Phosphor::UsersThree)
                ->color($mobilizationRate > 75 ? 'success' : ($mobilizationRate > 40 ? 'warning' : 'danger')),

            Stat::make('Rotation Moyenne', number_format($averageDistance, 1, ',', ' ').' km')
                ->description('Distance moyenne parcourue par affectation ce mois-ci')
                ->descriptionIcon(Phosphor::NavigationArrow)
                ->color('info'),
        ];
    }
}
