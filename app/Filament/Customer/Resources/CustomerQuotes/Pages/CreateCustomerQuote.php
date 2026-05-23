<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\Pages;

use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerQuote extends CreateRecord
{
    protected static string $resource = CustomerQuoteResource::class;
}
