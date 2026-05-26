<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Pages;

use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditCustomerInvoice extends EditRecord
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Editer Facture n°'.$this->getRecord()->reference;
    }
}
