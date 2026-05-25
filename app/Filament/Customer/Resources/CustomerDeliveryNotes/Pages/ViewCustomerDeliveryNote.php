<?php

namespace App\Filament\Customer\Resources\CustomerDeliveryNotes\Pages;

use App\Filament\Actions\PrinterAction;
use App\Filament\Customer\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerDeliveryNote extends ViewRecord
{
    protected static string $resource = CustomerDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            PrinterAction::make('livraison'),
        ];
    }
}
