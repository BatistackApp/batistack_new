<?php

namespace App\Filament\Customer\Resources\CustomerCreditNotes\Pages;

use App\Filament\Customer\Resources\CustomerCreditNotes\CustomerCreditNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerCreditNotes extends ListRecords
{
    protected static string $resource = CustomerCreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
