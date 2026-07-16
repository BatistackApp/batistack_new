<?php

namespace App\Filament\Commerce\Resources\ReceiptNotes\Pages;

use App\Filament\Commerce\Resources\ReceiptNotes\ReceiptNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReceiptNotes extends ListRecords
{
    protected static string $resource = ReceiptNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
