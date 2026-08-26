<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Pages;

use App\Filament\Commerce\Resources\CustomerInvoices\CustomerInvoiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerInvoices extends ListRecords
{
    protected static string $resource = CustomerInvoiceResource::class;

    protected static ?string $title = 'Liste des Factures';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nouvelle facture'),
        ];
    }
}
