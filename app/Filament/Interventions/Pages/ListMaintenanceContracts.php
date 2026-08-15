<?php

namespace App\Filament\Interventions\Pages;

use App\Filament\Interventions\MaintenanceContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceContracts extends ListRecords
{
    protected static string $resource = MaintenanceContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
