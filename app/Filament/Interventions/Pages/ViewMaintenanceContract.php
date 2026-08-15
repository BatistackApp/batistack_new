<?php

namespace App\Filament\Interventions\Pages;

use App\Filament\Interventions\MaintenanceContractResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMaintenanceContract extends ViewRecord
{
    protected static string $resource = MaintenanceContractResource::class;
}
