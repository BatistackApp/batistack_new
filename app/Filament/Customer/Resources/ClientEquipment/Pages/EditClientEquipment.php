<?php

namespace App\Filament\Customer\Resources\ClientEquipment\Pages;

use App\Filament\Customer\Resources\ClientEquipment\ClientEquipmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClientEquipment extends EditRecord
{
    protected static string $resource = ClientEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
