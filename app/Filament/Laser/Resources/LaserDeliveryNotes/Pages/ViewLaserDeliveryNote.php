<?php

namespace App\Filament\Laser\Resources\LaserDeliveryNotes\Pages;

use App\Enums\Laser\DeliveryStatus;
use App\Filament\Laser\Resources\LaserDeliveryNotes\LaserDeliveryNoteResource;
use App\Services\Laser\LaserDeliveryNoteService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewLaserDeliveryNote extends ViewRecord
{
    protected static string $resource = LaserDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('ship')
                ->label('Expédier')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->visible(fn ($record) => $record->status === DeliveryStatus::DRAFT)
                ->requiresConfirmation()
                ->modalHeading('Expédition du BL')
                ->modalDescription('Confirmer l\'expédition mettra à jour les quantités livrées de la commande.')
                ->action(function ($record) {
                    app(LaserDeliveryNoteService::class)->shipDeliveryNote($record);

                    Notification::make()
                        ->title('BL expédié')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('receive')
                ->label('Réceptionner')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status === DeliveryStatus::SHIPPED)
                ->requiresConfirmation()
                ->modalHeading('Réception du BL')
                ->modalDescription('Confirmer la réception par le client.')
                ->action(function ($record) {
                    app(LaserDeliveryNoteService::class)->receiveDeliveryNote($record);

                    Notification::make()
                        ->title('BL réceptionné')
                        ->success()
                        ->send();
                }),

            Actions\PrintAction::make()
                ->label('Imprimer PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => Storage::url('documents/laser/delivery_notes/bon_de_livraison_'.$record->reference.'.pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
