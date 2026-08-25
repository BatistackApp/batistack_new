<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\Pages;

use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerQuotes extends ListRecords
{
    protected static string $resource = CustomerQuoteResource::class;

    protected static ?string $title = 'Liste des Devis';

    protected static ?string $breadcrumb = 'Liste';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
