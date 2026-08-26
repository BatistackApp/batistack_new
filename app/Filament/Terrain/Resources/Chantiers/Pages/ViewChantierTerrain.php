<?php

namespace App\Filament\Terrain\Resources\Chantiers\Pages;

use Filament\Resources\Pages\ViewRecord;

class ViewChantierTerrain extends ViewRecord
{
    protected static string $resource = \App\Filament\Terrain\Resources\Chantiers\ChantierTerrainResource::class;

    protected static ?string $title = 'Détail du Chantier';
}
