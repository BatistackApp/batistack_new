<?php

namespace App\Filament\Commerce\Resources\SupplierInvoices\Pages;

use App\Filament\Commerce\Resources\SupplierInvoices\SupplierInvoiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSupplierInvoice extends ViewRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
