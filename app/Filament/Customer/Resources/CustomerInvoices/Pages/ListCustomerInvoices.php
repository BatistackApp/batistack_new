<?php

namespace App\Filament\Customer\Resources\CustomerInvoices\Pages;

use App\Filament\Customer\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerInvoices extends ListRecords
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
