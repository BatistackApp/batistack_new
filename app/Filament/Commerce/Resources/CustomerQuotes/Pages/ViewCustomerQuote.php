<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Pages;

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Core\SignatureType;
use App\Filament\Commerce\Resources\CustomerQuotes\CustomerQuoteResource;
use App\Models\Commerce\CustomerQuote;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\QuoteService;
use App\Services\Core\DocumentService;
use App\Services\Core\SignatureService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewCustomerQuote extends ViewRecord
{
    protected static string $resource = CustomerQuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sendingQuote')
                ->label('Envoyer le devis')
                ->icon(Phosphor::Envelope)
                ->color('primary')
                ->visible(fn (CustomerQuote $record) => $record->status === QuoteStatus::DRAFT || $record->status === QuoteStatus::SENT)
                ->requiresConfirmation()
                ->modalHeading('Envoyer le devis')
                ->modalDescription('Envoyer le devis au client va valider automatiquement le devis, vous ne pourrez plus le modifier, Etes-vous sur ?')
                ->modalIconColor('warning')
                ->color('warning')
                ->action(function (CustomerQuote $record) {
                    if ($record->items()->count() === 0) {
                        Notification::make()
                            ->danger()
                            ->title('Le devis ne contient aucune ligne')
                            ->send();

                        return;
                    }

                    $record->update(['status' => QuoteStatus::SENT]);

                    try {
                        $path = app(CommerceDocumentationService::class)->generateQuotePdf($record);

                        $client = $record->client;
                        $contact = $client?->getPrimaryContact();
                        $email = $contact?->email ?? $client?->email;
                        $name = $contact ? trim("{$contact->first_name} {$contact->last_name}") : ($client?->name ?? 'Client');

                        if ($email) {
                            app(SignatureService::class)->driver('local')->requestSignature(
                                model: $record,
                                type: SignatureType::AUTOGRAPH,
                                email: $email,
                                name: $name,
                                documentPath: $path
                            );

                            Notification::make()
                                ->success()
                                ->title('Devis envoyé avec demande de signature au client')
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Devis généré mais le client n\'a pas d\'email pour la signature')
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Erreur lors de la préparation de la signature')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            MediaAction::make()
                ->label('Imprimer PDF')
                ->icon(Phosphor::Printer)
                ->mediaType(MediaAction::TYPE_PDF)
                ->modalWidth(Width::Container)
                ->media(function (Model $record) {
                    $path = 'commerce/quotes/devis_'.$record->reference.'.pdf';
                    $disk = DocumentService::getDisk();

                    if (! Storage::disk($disk)->exists('documents/'.$path)) {
                        try {
                            app(CommerceDocumentationService::class)->generateQuotePdf($record);
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Erreur de génération PDF')
                                ->body($e->getMessage())
                                ->send();

                            return '';
                        }
                    }

                    if ($disk === 's3') {
                        return Storage::disk($disk)->temporaryUrl('documents/'.$path, now()->addMinutes(5));
                    }

                    return Storage::disk($disk)->url('documents/'.$path);
                }),

            ActionGroup::make([
                Action::make('manualAccept')
                    ->visible(fn (Model $record) => $record->status === QuoteStatus::SENT)
                    ->label('Accepter')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Accepter le devis')
                    ->modalDescription('Accepter le devis va valider automatiquement le devis, vous ne pourrez plus le modifier, Etes-vous sur ?')
                    ->action(function (Model $record) {
                        app(QuoteService::class)->acceptQuote($record, auth()->user());
                    }),

                Action::make('manualRefused')
                    ->visible(fn (Model $record) => $record->status === QuoteStatus::SENT)
                    ->label('Refuser')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Refuser le devis')
                    ->modalDescription('Refuser le devis va valider automatiquement le devis, vous ne pourrez plus le modifier, Etes-vous sur ?')
                    ->action(function (Model $record) {
                        $record->update(['status' => QuoteStatus::REJECTED]);
                    }),
            ]),
        ];
    }
}
