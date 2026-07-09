<?php

namespace App\Filament\Salarie\Resources\EquipementResource\Pages;

use App\Filament\Salarie\Resources\EquipementResource;
use Filament\Resources\Pages\ListRecords;

class ListEquipements extends ListRecords
{
    protected static string $resource = EquipementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
