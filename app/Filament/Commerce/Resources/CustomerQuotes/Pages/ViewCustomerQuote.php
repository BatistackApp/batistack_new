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
                ->modalDescription('Envoyer le devis au client va valider automatiquement le devis, vous ne pourrez plus le modifier.')
                ->modalIconColor('warning')
                ->color('warning')
                ->form([
                    Filament\Forms\Components\Toggle::make('is_multi')
                        ->label('Signature multi-signataires')
                        ->default(false)
                        ->live(),
                    Filament\Forms\Components\Repeater::make('signers')
                        ->label('Signataires')
                        ->schema([
                            Filament\Forms\Components\TextInput::make('name')
                                ->label('Nom')
                                ->required(),
                            Filament\Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required(),
                            Filament\Forms\Components\Select::make('role')
                                ->label('Rôle')
                                ->options([
                                    'Signataire' => 'Signataire',
                                    'Client' => 'Client',
                                    'Manager' => 'Manager',
                                    'Comptable' => 'Comptable',
                                    'Autre' => 'Autre',
                                ])
                                ->default('Signataire'),
                        ])
                        ->columns(3)
                        ->defaultItems(0)
                        ->addActionLabel('Ajouter un signataire')
                        ->visible(fn (Filament\Forms\Components\Get $get) => $get('is_multi')),
                ])
                ->action(function (CustomerQuote $record, array $data) {
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

                        $service = app(SignatureService::class);

                        if ($data['is_multi'] ?? false) {
                            $service->requestMultiSignature(
                                $record,
                                SignatureType::AUTOGRAPH,
                                $data['signers'],
                                $path
                            );

                            Notification::make()
                                ->success()
                                ->title('Devis envoyé avec demande de signature multi-signataires')
                                ->send();
                        } else {
                            $client = $record->client;
                            $contact = $client?->getPrimaryContact();
                            $email = $contact?->email ?? $client?->email;
                            $name = $contact ? trim("{$contact->first_name} {$contact->last_name}") : ($client?->name ?? 'Client');

                            if ($email) {
                                $service->requestSignature(
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
