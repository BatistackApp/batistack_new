<?php

namespace App\Filament\Locations\Resources\Locations\SupplierPriceGrids\Pages;

use App\Filament\Locations\Resources\Locations\SupplierPriceGrids\SupplierPriceGridResource;
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
