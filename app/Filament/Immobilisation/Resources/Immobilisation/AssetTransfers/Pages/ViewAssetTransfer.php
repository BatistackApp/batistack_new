<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\AssetTransferResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAssetTransfer extends ViewRecord
{
    protected static string $resource = AssetTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
