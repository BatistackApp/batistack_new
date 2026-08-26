<?php

namespace App\Filament\Flottes\Widgets;

use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use LaBoiteACode\FilamentDashboardWidgets\Data\Composition;
use LaBoiteACode\FilamentDashboardWidgets\Data\CompositionSlice;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\CompositionWidget;

class FleetCompositionWidget extends CompositionWidget
{
    protected static ?int $sort = 4;

    protected function getComposition(): Composition
    {
        $available = Vehicle::where('status', VehicleStatus::AVAILABLE)->count();
        $assigned = Vehicle::where('status', VehicleStatus::ASSIGNED)->count();
        $maintenance = Vehicle::where('status', VehicleStatus::MAINTENANCE)->count();
        $broken = Vehicle::where('status', VehicleStatus::BROKEN)->count();

        return Composition::make('Statut du Parc')
            ->slices([
                CompositionSlice::make('Disponibles', $available)->color('success'),
                CompositionSlice::make('En mission', $assigned)->color('primary'),
                CompositionSlice::make('Au garage', $maintenance)->color('warning'),
                CompositionSlice::make('Sinistrés', $broken)->color('danger'),
            ])
            ->type('doughnut');
    }
}
