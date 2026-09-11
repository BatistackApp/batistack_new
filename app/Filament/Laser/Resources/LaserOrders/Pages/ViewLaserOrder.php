<?php

namespace App\Filament\Laser\Resources\LaserOrders\Pages;

use App\Enums\Laser\OrderStatus;
use App\Filament\Laser\Resources\LaserOrders\LaserOrderResource;
use App\Services\Laser\LaserDeliveryNoteService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewLaserOrder extends ViewRecord
{
    protected static string $resource = LaserOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Confirmer')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->status === OrderStatus::DRAFT)
                ->requiresConfirmation()
                ->modalHeading('Confirmer la commande')
                ->modalDescription('La confirmation validera la commande et générera le document PDF.')
                ->action(function ($record) {
                    $record->update(['status' => OrderStatus::CONFIRMED]);

                    Notification::make()
                        ->title('Commande confirmée')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('generateDeliveryNote')
                ->label('Générer un Bon de Livraison')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->visible(function ($record) {
                    if (! in_array($record->status, [OrderStatus::CONFIRMED, OrderStatus::IN_PROGRESS])) {
                        return false;
                    }

                    $record->load('lines');

                    return $record->lines->some(fn ($line) => $line->remaining_quantity > 0);
                })
                ->requiresConfirmation()
                ->modalHeading('Générer un Bon de Livraison')
                ->modalDescription('Un bon de livraison sera créé avec les lignes restant à livrer.')
                ->action(function ($record) {
                    $bl = app(LaserDeliveryNoteService::class)->createDeliveryNote($record);

                    Notification::make()
                        ->title('BL créé : '.$bl->reference)
                        ->success()
                        ->send();

                    return $this->redirect(route('filament.laser.resources.laser-delivery-notes.view', $bl));
                }),

            Actions\Action::make('cancel')
                ->label('Annuler')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn ($record) => in_array($record->status, [OrderStatus::DRAFT, OrderStatus::CONFIRMED]))
                ->requiresConfirmation()
                ->modalHeading('Annuler la commande')
                ->modalDescription('Cette action est irréversible.')
                ->action(function ($record) {
                    $record->update(['status' => OrderStatus::CANCELLED]);

                    Notification::make()
                        ->title('Commande annulée')
                        ->danger()
                        ->send();
                }),

            Actions\PrintAction::make()
                ->label('Imprimer PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => Storage::url('documents/laser/orders/commande_laser_'.$record->reference.'.pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
