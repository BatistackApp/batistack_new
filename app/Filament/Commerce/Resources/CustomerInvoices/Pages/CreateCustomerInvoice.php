<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Pages;

use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerInvoice extends CreateRecord
{
    protected static string $resource = CustomerInvoiceResource::class;
}
