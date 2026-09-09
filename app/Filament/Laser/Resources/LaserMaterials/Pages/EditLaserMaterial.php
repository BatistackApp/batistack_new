<?php

namespace App\Filament\Laser\Resources\LaserMaterials\Pages;

use App\Filament\Laser\Resources\LaserMaterials\LaserMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLaserMaterial extends EditRecord
{
    protected static string $resource = LaserMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
