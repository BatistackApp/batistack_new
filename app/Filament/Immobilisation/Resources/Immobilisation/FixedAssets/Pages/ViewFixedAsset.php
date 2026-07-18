<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\FixedAssetResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFixedAsset extends ViewRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
