<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Pages;

use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerInvoice extends ViewRecord
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
