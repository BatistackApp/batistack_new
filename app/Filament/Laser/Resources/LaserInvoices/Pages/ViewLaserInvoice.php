<?php

namespace App\Filament\Laser\Resources\LaserInvoices\Pages;

use App\Enums\Laser\InvoiceStatus;
use App\Filament\Laser\Resources\LaserInvoices\LaserInvoiceResource;
use App\Services\Laser\LaserInvoiceService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewLaserInvoice extends ViewRecord
{
    protected static string $resource = LaserInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('legalize')
                ->label('Légaliser')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn ($record) => $record->status === InvoiceStatus::DRAFT)
                ->requiresConfirmation()
                ->modalHeading('Légalisation de la facture')
                ->modalDescription('La légalisation appliquera la chaîne de hash NF525. Cette action est irréversible.')
                ->action(function ($record) {
                    app(LaserInvoiceService::class)->legalizeInvoice($record);

                    Notification::make()
                        ->title('Facture légalisée')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('delete')
                ->label('Supprimer')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn ($record) => $record->canBeDeleted())
                ->requiresConfirmation()
                ->modalHeading('Suppression de la facture')
                ->modalDescription('Les quantités facturées seront restituées sur la commande. Cette action est irréversible.')
                ->action(function ($record) {
                    app(LaserInvoiceService::class)->deleteInvoice($record);

                    Notification::make()
                        ->title('Facture supprimée')
                        ->success()
                        ->send();

                    return $this->redirect(route('filament.laser.resources.laser-invoices.index'));
                }),

            Actions\Action::make('createCreditNote')
                ->label('Créer un avoir')
                ->icon('heroicon-o-document-minus')
                ->color('warning')
                ->visible(fn ($record) => $record->canBeCredited())
                ->form([
                    TextInput::make('total_ht')
                        ->label('Montant HT (laisser vide pour le solde complet)')
                        ->suffix('€')
                        ->numeric()
                        ->minValue(0.01)
                        ->max(fn ($record) => $record->remaining_creditable_ht)
                        ->helperText(fn ($record) => 'Solde restant : '.number_format($record->remaining_creditable_ht, 2, ',', ' ').' € HT'),
                    Textarea::make('reason')
                        ->label('Motif')
                        ->required()
                        ->rows(3),
                ])
                ->modalHeading('Créer un avoir')
                ->modalDescription('L\'avoir sera créé et validé immédiatement.')
                ->action(function ($record, array $data) {
                    $totalHt = $data['total_ht'] !== null ? (float) $data['total_ht'] : null;

                    $creditNote = app(LaserInvoiceService::class)->createCreditNote(
                        $record,
                        $data['reason'],
                        $totalHt
                    );

                    Notification::make()
                        ->title('Avoir créé : '.$creditNote->reference)
                        ->success()
                        ->send();

                    return $this->redirect(route('filament.laser.resources.laser-credit-notes.view', $creditNote));
                }),

            Actions\PrintAction::make()
                ->label('Imprimer PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => Storage::url('documents/laser/invoices/facture_'.$record->reference.'.pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
