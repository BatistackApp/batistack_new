<?php

namespace App\Filament\Customer\Resources\ClientEquipment\Pages;

use App\Filament\Customer\Resources\ClientEquipment\ClientEquipmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClientEquipment extends ViewRecord
{
    protected static string $resource = ClientEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
