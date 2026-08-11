<?php

namespace App\Filament\Locations\Resources\Locations\SupplierPriceGrids\Pages;

use App\Filament\Locations\Resources\Locations\SupplierPriceGrids\SupplierPriceGridResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierPriceGrids extends ListRecords
{
    protected static string $resource = SupplierPriceGridResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
