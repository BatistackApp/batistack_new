<?php

namespace App\Filament\Commerce\Resources\SupplierInvoices\Pages;

use App\Filament\Commerce\Resources\SupplierInvoices\SupplierInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierInvoices extends ListRecords
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
