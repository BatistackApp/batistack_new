<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\AssetMaintenanceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetMaintenance extends EditRecord
{
    protected static string $resource = AssetMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
