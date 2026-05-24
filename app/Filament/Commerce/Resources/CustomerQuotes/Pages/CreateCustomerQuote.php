<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Pages;

use App\Filament\Commerce\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomerQuote extends CreateRecord
{
    protected static string $resource = CustomerQuoteResource::class;
}
