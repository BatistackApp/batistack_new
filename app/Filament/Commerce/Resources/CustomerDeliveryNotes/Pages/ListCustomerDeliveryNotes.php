<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages;

use App\Filament\Commerce\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerDeliveryNotes extends ListRecords
{
    protected static string $resource = CustomerDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
