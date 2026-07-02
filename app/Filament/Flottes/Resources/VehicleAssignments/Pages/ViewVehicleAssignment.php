<?php

namespace App\Filament\Flottes\Resources\VehicleAssignments\Pages;

use App\Filament\Flottes\Resources\VehicleAssignments\VehicleAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewVehicleAssignment extends ViewRecord
{
    protected static string $resource = VehicleAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
