<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages;

use App\Filament\Commerce\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDeliveryNote extends CreateRecord
{
    protected static string $resource = CustomerDeliveryNoteResource::class;
}
