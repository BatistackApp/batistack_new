<?php

namespace App\Filament\Customer\Resources\CustomerDeliveryNotes\Pages;

use App\Filament\Customer\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerDeliveryNotes extends ListRecords
{
    protected static string $resource = CustomerDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
