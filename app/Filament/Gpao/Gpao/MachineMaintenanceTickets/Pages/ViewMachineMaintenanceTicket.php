<?php

namespace App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Pages;

use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Actions\ResolvableTicketActions;
use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\MachineMaintenanceTicketResource;
use Filament\Resources\Pages\ViewRecord;

class ViewMachineMaintenanceTicket extends ViewRecord
{
    use ResolvableTicketActions;

    protected static string $resource = MachineMaintenanceTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            static::getStartAction(),
            static::getResolveAction(),
            static::getCancelAction(),
        ];
    }
}
