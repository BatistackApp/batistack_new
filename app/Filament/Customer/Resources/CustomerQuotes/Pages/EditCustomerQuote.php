<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\Pages;

use App\Filament\Customer\Resources\CustomerQuotes\CustomerQuoteResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerQuote extends EditRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
