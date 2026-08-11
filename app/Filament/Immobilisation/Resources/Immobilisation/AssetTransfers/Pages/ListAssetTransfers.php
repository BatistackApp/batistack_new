<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Pages;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\AssetTransferResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetTransfers extends ListRecords
{
    protected static string $resource = AssetTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
