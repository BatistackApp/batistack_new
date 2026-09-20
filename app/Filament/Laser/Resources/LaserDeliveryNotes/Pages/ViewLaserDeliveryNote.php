<?php

namespace App\Filament\Laser\Resources\LaserDeliveryNotes\Pages;

use App\Enums\Laser\DeliveryStatus;
use App\Filament\Laser\Resources\LaserDeliveryNotes\LaserDeliveryNoteResource;
use App\Services\Laser\LaserDeliveryNoteService;
use App\Services\Laser\LaserInvoiceService;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;

class ViewLaserDeliveryNote extends ViewRecord
{
    use HasPageHeader;

    protected static string $resource = LaserDeliveryNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->status === DeliveryStatus::DRAFT),

            Actions\Action::make('delete')
                ->label('Supprimer')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn ($record) => $record->canBeDeleted())
                ->requiresConfirmation()
                ->action(function ($record) {
                    app(LaserDeliveryNoteService::class)->deleteDeliveryNote($record);

                    Notification::make()->title('Bon de livraison supprimé')->success()->send();

                    return $this->redirect(route('filament.laser.resources.laser-delivery-notes.index'));
                }),

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
                ->form([
                    DatePicker::make('delivery_date')
                        ->label('Date de livraison effective')
                        ->default(now())
                        ->required()
                        ->native(false),
                ])
                ->action(function ($record, array $data) {
                    app(LaserDeliveryNoteService::class)->receiveDeliveryNote($record, $data['delivery_date']);

                    Notification::make()
                        ->title('BL réceptionné')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('createInvoice')
                ->label('Créer une facture')
                ->icon('heroicon-o-document-text')
                ->color('warning')
                ->visible(function ($record) {
                    if ($record->status !== DeliveryStatus::SHIPPED && $record->status !== DeliveryStatus::DELIVERED) {
                        return false;
                    }

                    $record->load('order.lines');

                    return $record->order->lines->some(
                        fn ($line) => $line->delivered_quantity > $line->invoiced_quantity
                    );
                })
                ->requiresConfirmation()
                ->modalHeading('Créer une facture')
                ->modalDescription('Une facture sera créée pour les lignes livrées non encore facturées.')
                ->action(function ($record) {
                    $invoice = app(LaserInvoiceService::class)->createInvoice($record->order);

                    Notification::make()
                        ->title('Facture créée : '.$invoice->reference)
                        ->success()
                        ->send();

                    return $this->redirect(route('filament.laser.resources.laser-invoices.view', $invoice));
                }),

            Actions\Action::make('print')
                ->label('Imprimer PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => Storage::url('documents/laser/delivery_notes/bon_de_livraison_'.$record->reference.'.pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
