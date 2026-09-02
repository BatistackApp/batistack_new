<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Pages;

use App\Filament\Commerce\Resources\CustomerDeliveryNotes\CustomerDeliveryNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDeliveryNote extends EditRecord
{
    protected static string $resource = CustomerDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->visible(fn () => $this->record->canBeDeleted()),
        ];
    }
}
