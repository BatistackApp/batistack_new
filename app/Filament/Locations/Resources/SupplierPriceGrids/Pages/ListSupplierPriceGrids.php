<?php

namespace App\Filament\Locations\Resources\SupplierPriceGrids\Pages;

use App\Filament\Locations\Resources\SupplierPriceGrids\SupplierPriceGridResource;
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
