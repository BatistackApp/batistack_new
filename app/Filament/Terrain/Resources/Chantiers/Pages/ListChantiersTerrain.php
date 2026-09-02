<?php

namespace App\Filament\Terrain\Resources\Chantiers\Pages;

use App\Filament\Terrain\Resources\Chantiers\ChantierTerrainResource;
use Filament\Resources\Pages\ListRecords;

class ListChantiersTerrain extends ListRecords
{
    protected static string $resource = ChantierTerrainResource::class;

    protected static ?string $title = 'Mes Chantiers';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
