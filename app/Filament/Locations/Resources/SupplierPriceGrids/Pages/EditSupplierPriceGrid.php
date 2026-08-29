<?php

namespace App\Filament\Locations\Resources\SupplierPriceGrids\Pages;

use App\Filament\Locations\Resources\SupplierPriceGrids\SupplierPriceGridResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierPriceGrid extends EditRecord
{
    protected static string $resource = SupplierPriceGridResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
