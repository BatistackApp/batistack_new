<?php

namespace App\Filament\Flottes\Widgets;

use App\Models\Flottes\Vehicle;
use LaBoiteACode\FilamentDashboardWidgets\Widgets\DetailListWidget;
use LaBoiteACode\FilamentDashboardWidgets\Data\Detail;
use App\Filament\Flottes\Resources\Vehicles\VehicleResource;

class FleetAlertsDetailWidget extends DetailListWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function getHeading(): string
    {
        return 'Alertes de Maintenance & Amendes';
    }

    protected function getDetails(): array
    {
        $vehicles = Vehicle::pollutionControlDue()->get();
        
        return $vehicles->map(function ($v) {
            return Detail::make($v->getDisplayName(), "Contrôle technique ou pollution dépassé le " . $v->pollution_control_due_at->format('d/m/Y'))
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->url(VehicleResource::getUrl('edit', ['record' => $v]));
        })->toArray();
    }
}
