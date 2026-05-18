<?php

namespace App\Filament\Flottes\Resources\VehicleAssignments\Pages;

use App\Filament\Flottes\Resources\VehicleAssignments\VehicleAssignmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVehicleAssignment extends EditRecord
{
    protected static string $resource = VehicleAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
