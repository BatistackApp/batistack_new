<?php

namespace App\Filament\Gpao\Gpao\Machines\Pages;

use App\Filament\Gpao\Gpao\Machines\MachineResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMachine extends EditRecord
{
    protected static string $resource = MachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
