<?php

namespace App\Filament\Customer\Resources\CustomerInvoices\Pages;

use App\Filament\Customer\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerInvoice extends ViewRecord
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
