<?php

namespace App\Filament\Commerce\Resources\SupplierCreditNotes\Pages;

use App\Filament\Commerce\Resources\SupplierCreditNotes\SupplierCreditNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSupplierCreditNote extends EditRecord
{
    protected static string $resource = SupplierCreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->visible(fn () => $this->record->canBeDeleted()),
        ];
    }
}
