<?php

namespace App\Filament\Interventions\Pages;

use App\Filament\Interventions\MaintenanceContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceContract extends CreateRecord
{
    protected static string $resource = MaintenanceContractResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
