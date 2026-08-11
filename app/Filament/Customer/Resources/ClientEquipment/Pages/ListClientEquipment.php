<?php

namespace App\Filament\Customer\Resources\ClientEquipment\Pages;

use App\Filament\Customer\Resources\ClientEquipment\ClientEquipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientEquipment extends ListRecords
{
    protected static string $resource = ClientEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
