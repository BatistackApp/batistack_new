<?php

namespace App\Filament\Commerce\Resources\CustomerCreditNotes\Pages;

use App\Filament\Commerce\Resources\CustomerCreditNotes\CustomerCreditNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerCreditNote extends EditRecord
{
    protected static string $resource = CustomerCreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->visible(fn () => $this->record->status === \App\Enums\Commerce\InvoiceStatus::DRAFT),
        ];
    }
}
