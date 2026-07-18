<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\FixedAssetResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditFixedAsset extends EditRecord
{
    protected static string $resource = FixedAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
