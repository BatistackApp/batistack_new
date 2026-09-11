<?php

namespace App\Filament\Laser\Resources\LaserCreditNotes\Pages;

use App\Filament\Laser\Resources\LaserCreditNotes\LaserCreditNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewLaserCreditNote extends ViewRecord
{
    protected static string $resource = LaserCreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\PrintAction::make()
                ->label('Imprimer PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => Storage::url('documents/laser/credit_notes/avoir_'.$record->reference.'.pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
