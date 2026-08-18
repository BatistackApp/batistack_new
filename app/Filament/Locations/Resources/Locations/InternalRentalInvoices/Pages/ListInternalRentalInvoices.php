<?php

namespace App\Filament\Locations\Resources\Locations\InternalRentalInvoices\Pages;

use App\Filament\Locations\Resources\Locations\InternalRentalInvoices\InternalRentalInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInternalRentalInvoices extends ListRecords
{
    protected static string $resource = InternalRentalInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
