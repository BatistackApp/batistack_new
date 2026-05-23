<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\Pages;

use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerQuote extends ViewRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
