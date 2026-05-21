<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Pages;

use App\Filament\Commerce\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerQuote extends CreateRecord
{
    protected static string $resource = CustomerQuoteResource::class;
}
