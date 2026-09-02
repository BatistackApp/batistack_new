<?php

namespace App\Filament\Terrain\Resources\Chantiers\Pages;

use App\Filament\Terrain\Resources\Chantiers\ChantierTerrainResource;
use Filament\Resources\Pages\ViewRecord;

class ViewChantierTerrain extends ViewRecord
{
    protected static string $resource = ChantierTerrainResource::class;

    protected static ?string $title = 'Détail du Chantier';
}
