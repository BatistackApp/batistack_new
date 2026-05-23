<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\Pages;

use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerQuotes extends ListRecords
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
