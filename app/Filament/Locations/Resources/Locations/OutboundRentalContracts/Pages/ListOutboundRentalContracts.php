<?php

namespace App\Filament\Locations\Resources\Locations\OutboundRentalContracts\Pages;

use App\Filament\Locations\Resources\Locations\OutboundRentalContracts\OutboundRentalContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOutboundRentalContracts extends ListRecords
{
    protected static string $resource = OutboundRentalContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
