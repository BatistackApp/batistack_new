<?php

namespace App\Filament\Salarie\Resources\ContractResource\Pages;

use App\Filament\Salarie\Resources\ContractResource;
use Filament\Resources\Pages\ListRecords;

class ListContracts extends ListRecords
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
