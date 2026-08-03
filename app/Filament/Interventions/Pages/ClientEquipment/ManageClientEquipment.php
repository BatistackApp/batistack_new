<?php

namespace App\Filament\Interventions\Pages\ClientEquipment;

use App\Filament\Interventions\ClientEquipmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageClientEquipment extends ManageRecords
{
    protected static string $resource = ClientEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
