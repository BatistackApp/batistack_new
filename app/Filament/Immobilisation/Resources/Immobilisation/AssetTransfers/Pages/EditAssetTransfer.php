<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\AssetTransferResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetTransfer extends EditRecord
{
    protected static string $resource = AssetTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
