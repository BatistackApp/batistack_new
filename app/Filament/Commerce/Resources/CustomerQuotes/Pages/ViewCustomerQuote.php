<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Pages;

use App\Filament\Commerce\Resources\CustomerQuotes\CustomerQuoteResource;
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
