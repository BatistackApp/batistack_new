<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\AssetMaintenanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetMaintenances extends ListRecords
{
    protected static string $resource = AssetMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
