<?php

namespace App\Filament\Laser\Resources\LaserCreditNotes\Pages;

use App\Jobs\Laser\SyncLaserAccountingJob;
use App\Filament\Laser\Resources\LaserCreditNotes\LaserCreditNoteResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewLaserCreditNote extends ViewRecord
{
    protected static string $resource = LaserCreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('retryAccountingSync')
                ->label('Relancer la comptabilisation')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn ($record) => in_array($record->accountingSync?->status, ['pending', 'failed'], true))
                ->action(function ($record) {
                    SyncLaserAccountingJob::dispatch('credit_note', $record->id);

                    Notification::make()
                        ->title('Synchronisation comptable relancée')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('print')
                ->label('Imprimer PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => Storage::url('documents/laser/credit_notes/avoir_'.$record->reference.'.pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
