<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Tables;

use App\Enums\Commerce\QuoteStatus;
use App\Enums\Core\SignatureType;
use App\Filament\Commerce\Resources\CustomerOrders\CustomerOrderResource;
use App\Models\Commerce\CustomerQuote;
use App\Services\Commerce\CommerceDocumentationService;
use App\Services\Commerce\QuoteService;
use App\Services\Core\DocumentService;
use App\Services\Core\SignatureService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Hugomyb\FilamentMediaAction\Actions\MediaAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerQuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('is_avenant')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Avenant' : 'Devis')
                    ->color(fn (bool $state) => $state ? 'warning' : 'gray'),

                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')->label('Créé le')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->options(QuoteStatus::class),

                SelectFilter::make('is_avenant')->label('Type')
                    ->options([
                        '0' => 'Devis',
                        '1' => 'Avenant',
                    ]),

                SelectFilter::make('client_id')->label('Client')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make()
                        ->requiresConfirmation()
                        ->visible(fn ($record) => $record->canBeDeleted()),
                    Action::make('sendQuote')
                        ->label('Envoyer')
                        ->icon('heroicon-o-paper-airplane')
                        ->visible(fn (CustomerQuote $record) => $record->status === QuoteStatus::DRAFT)
                        ->action(fn (CustomerQuote $record) => $record->update(['status' => QuoteStatus::SENT]))
                        ->requiresConfirmation(),

                    Action::make('acceptQuote')
                        ->label('Accepter')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (CustomerQuote $record) => in_array($record->status, [QuoteStatus::DRAFT, QuoteStatus::SENT]))
                        ->requiresConfirmation()
                        ->modalHeading('Accepter le devis')
                        ->modalDescription('Êtes-vous sûr de vouloir accepter ce devis ? Cela générera automatiquement la commande client.')
                        ->modalSubmitActionLabel('Oui, accepter')
                        ->action(function (CustomerQuote $record) {
                            try {
                                $order = app(QuoteService::class)->acceptQuote($record, auth()->user());

                                if ($record->is_avenant) {
                                    Notification::make()
                                        ->success()
                                        ->title('Avenant accepté')
                                        ->body('Les travaux supplémentaires ont été ajoutés à la commande principale et le budget du chantier a été rehaussé.')
                                        ->send();

                                    return redirect(CustomerOrderResource::getUrl('view', ['record' => $order]));
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Devis accepté et commande générée')
                                    ->send();

                                return redirect(CustomerOrderResource::getUrl('view', ['record' => $order]));
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Erreur')
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

                    Action::make('requestSignature')
                        ->label('Signature eIDAS')
                        ->icon(Phosphor::SealCheck)
                        ->color('info')
                        ->form([
                            TextInput::make('name')->label('Nom')
                                ->label('Nom du signataire')
                                ->required()
                                ->default(fn (CustomerQuote $record) => optional($record->client)->name),
                            TextInput::make('email')->label('Email')
                                ->label('Email du signataire')
                                ->email()
                                ->required()
                                ->default(fn (CustomerQuote $record) => optional($record->client)->email),
                        ])
                        ->modalHeading('Demande de signature DocuSeal')
                        ->modalDescription('Un e-mail certifié sera envoyé au client avec le devis.')
                        ->action(function (CustomerQuote $record, array $data) {
                            try {
                                $path = 'commerce/quotes/devis_'.$record->reference.'.pdf';
                                $disk = DocumentService::getDisk();

                                // S'assurer que le PDF est généré
                                if (! Storage::disk($disk)->exists('documents/'.$path)) {
                                    app(CommerceDocumentationService::class)->generateQuotePdf($record);
                                }

                                app(SignatureService::class)->requestSignature(
                                    $record,
                                    SignatureType::EIDAS,
                                    $data['email'],
                                    $data['name'],
                                    'documents/'.$path
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Demande envoyée !')
                                    ->body('Le devis a bien été envoyé pour signature.')
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Erreur')
                                    ->body($e->getMessage())
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->check(fn ($records) => $records->every(fn ($r) => $r->canBeDeleted())),
                ]),
            ]);
    }
}
