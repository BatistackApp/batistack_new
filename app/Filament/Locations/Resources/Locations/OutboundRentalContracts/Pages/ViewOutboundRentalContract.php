<?php

namespace App\Filament\Locations\Resources\Locations\OutboundRentalContracts\Pages;

use App\Filament\Locations\Resources\Locations\OutboundRentalContracts\OutboundRentalContractResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOutboundRentalContract extends ViewRecord
{
    protected static string $resource = OutboundRentalContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
