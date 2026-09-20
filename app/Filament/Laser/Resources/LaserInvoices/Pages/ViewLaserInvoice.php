<?php

namespace App\Filament\Laser\Resources\LaserInvoices\Pages;

use App\Enums\Laser\InvoiceStatus;
use App\Enums\Commerce\PaymentMethod;
use App\Enums\Commerce\PaymentType;
use App\Filament\Laser\Resources\LaserInvoices\LaserInvoiceResource;
use App\Jobs\Laser\SyncLaserAccountingJob;
use App\Services\Laser\LaserInvoiceService;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\Laser\LaserInvoiceMail;
use App\Services\Laser\LaserDocumentationService;
use App\Services\Commerce\PaymentRecordingService;
use App\Services\Commerce\PaymentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use MortalKiller\FilamentPageHeader\Concerns\HasPageHeader;

class ViewLaserInvoice extends ViewRecord
{
    use HasPageHeader;

    protected static string $resource = LaserInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn ($record) => $record->status === InvoiceStatus::DRAFT),

            Actions\Action::make('legalize')
                ->label('Légaliser')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn ($record) => $record->status === InvoiceStatus::DRAFT)
                ->requiresConfirmation()
                ->modalHeading('Légalisation de la facture')
                ->modalDescription('La légalisation appliquera la chaîne de hash NF525. Cette action est irréversible.')
                ->form([
                    Toggle::make('send_email')
                        ->label('Envoyer la facture au client par email')
                        ->default(true),
                ])
                ->action(function ($record, array $data) {
                    app(LaserInvoiceService::class)->legalizeInvoice($record);

                    if ($data['send_email'] ?? false) {
                        $record->load('client.primaryContact');
                        $contact = $record->client?->primaryContact;
                        $email = $contact?->email ?? $record->client?->email;

                        if ($email) {
                            $pdfPath = app(LaserDocumentationService::class)->generateInvoicePdf($record->fresh(['client', 'order', 'lines.material']));
                            Mail::to($email)->queue(new LaserInvoiceMail($record->fresh('client'), $pdfPath));

                            Notification::make()
                                ->title('Facture légalisée et envoyée au client')
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Facture légalisée, mais aucun email client trouvé')
                                ->warning()
                                ->send();
                        }
                    } else {
                        Notification::make()
                            ->title('Facture légalisée')
                            ->success()
                            ->send();
                    }
                }),

            Actions\Action::make('retryAccountingSync')
                ->label('Relancer la comptabilisation')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn ($record) => in_array($record->accountingSync?->status, ['pending', 'failed'], true))
                ->action(function ($record) {
                    SyncLaserAccountingJob::dispatch('invoice', $record->id);

                    Notification::make()
                        ->title('Synchronisation comptable relancée')
                        ->success()
                        ->send();
                    }),

            Actions\Action::make('recordPayment')
                ->label('Enregistrer un paiement')
                ->icon('heroicon-o-banknotes')
                ->color('primary')
                ->visible(fn ($record) => $record->status === InvoiceStatus::VALIDATED
                    && $record->paid_amount < (float) $record->total_ttc)
                ->form([
                    TextInput::make('amount')
                        ->label('Montant encaissé')
                        ->numeric()
                        ->minValue(0.01)
                        ->maxValue(fn ($record): float => max(0, (float) $record->total_ttc - (float) $record->payments()->sum('allocated_amount')))
                        ->required()
                        ->prefix('€'),
                    Select::make('method')
                        ->label('Moyen de paiement')
                        ->options(PaymentMethod::class)
                        ->required()
                        ->native(false),
                    TextInput::make('reference')
                        ->label('Référence du paiement')
                        ->required(),
                    DatePicker::make('payment_date')
                        ->label('Date du paiement')
                        ->default(now())
                        ->required()
                        ->native(false),
                ])
                ->action(function ($record, array $data) {
                    DB::transaction(function () use ($record, $data): void {
                        $payment = app(PaymentRecordingService::class)->recordPayment(
                            third_party: $record->client,
                            type: PaymentType::IN,
                            method: $data['method'] instanceof PaymentMethod
                                ? $data['method']
                                : PaymentMethod::from($data['method']),
                            amount: (float) $data['amount'],
                            payment_date: Carbon::parse($data['payment_date']),
                            reference: $data['reference'],
                        );

                        app(PaymentService::class)->allocatePayment(
                            $payment,
                            $record,
                            (float) $data['amount'],
                        );
                    });

                    Notification::make()
                        ->title('Paiement enregistré')
                        ->body('Le paiement a été affecté à la facture.')
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
                        ->maxValue(fn ($record) => $record->remaining_creditable_ht)
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

            Actions\Action::make('print')
                ->label('Imprimer PDF')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(fn ($record) => Storage::url('documents/laser/invoices/facture_'.$record->reference.'.pdf'))
                ->openUrlInNewTab(),
        ];
    }
}
