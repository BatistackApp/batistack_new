<?php

namespace App\Filament\Locations\Resources\OutboundRentalContracts\Pages;

use App\Filament\Locations\Resources\OutboundRentalContracts\OutboundRentalContractResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOutboundRentalContract extends EditRecord
{
    protected static string $resource = OutboundRentalContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
