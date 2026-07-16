<?php

namespace App\Filament\Commerce\Resources\ReceiptNotes\Pages;

use App\Filament\Commerce\Resources\ReceiptNotes\ReceiptNoteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReceiptNote extends EditRecord
{
    protected static string $resource = ReceiptNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
