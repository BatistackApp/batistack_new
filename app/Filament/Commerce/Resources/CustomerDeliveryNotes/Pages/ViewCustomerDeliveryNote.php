<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages;

use App\Filament\Commerce\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerDeliveryNote extends ViewRecord
{
    protected static string $resource = CustomerDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
