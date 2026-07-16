<?php

namespace App\Filament\Commerce\Resources\CustomerCreditNotes\Pages;

use App\Filament\Commerce\Resources\CustomerCreditNotes\CustomerCreditNoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerCreditNote extends CreateRecord
{
    protected static string $resource = CustomerCreditNoteResource::class;
}
