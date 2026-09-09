<?php

namespace App\Filament\Laser\Resources\LaserMaterials\Pages;

use App\Filament\Laser\Resources\LaserMaterials\LaserMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLaserMaterials extends ListRecords
{
    protected static string $resource = LaserMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
