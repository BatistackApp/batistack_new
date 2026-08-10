<?php

namespace App\Filament\Gpao\Gpao\Machines\Pages;

use App\Filament\Gpao\Gpao\Machines\MachineResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMachines extends ListRecords
{
    protected static string $resource = MachineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
