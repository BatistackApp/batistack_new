<?php

namespace App\Filament\Terrain\Resources\Chantiers\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListChantiersTerrain extends ListRecords
{
    protected static string $resource = \App\Filament\Terrain\Resources\Chantiers\ChantierTerrainResource::class;

    protected static ?string $title = 'Mes Chantiers';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
