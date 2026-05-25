<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Pages;

use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerInvoice extends EditRecord
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
