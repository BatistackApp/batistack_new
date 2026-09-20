<?php

namespace App\Filament\Laser\Resources\LaserQuotes\Tables;

use App\Enums\Laser\QuoteStatus;
use App\Enums\Core\SignatureType;
use App\Models\Laser\LaserQuote;
use App\Services\Laser\LaserQuoteService;
use App\Services\Core\SignatureService;
use App\Services\Laser\LaserDocumentationService;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LaserQuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(QuoteStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),

                    Action::make('sendForSignature')
                        ->label('Envoyer pour signature')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->visible(fn (LaserQuote $record) => in_array($record->status, [QuoteStatus::DRAFT, QuoteStatus::SENT], true))
                        ->action(function (LaserQuote $record): void {
                            $contact = $record->client?->getPrimaryContact();
                            $email = $contact?->email ?? $record->client?->email;
                            if (! $email) {
                                Notification::make()->warning()->title('Le client n’a pas d’adresse email')->send();
                                return;
                            }
                            $record->update(['status' => QuoteStatus::SENT]);
                            $path = app(LaserDocumentationService::class)->generateQuotePdf($record);
                            app(SignatureService::class)->requestSignature($record, SignatureType::AUTOGRAPH, $email, $contact ? trim("{$contact->first_name} {$contact->last_name}") : $record->client->name, $path);
                            Notification::make()->success()->title('Devis envoyé pour signature')->send();
                        }),

                    Action::make('convertToOrder')
                        ->label('Transformer en commande')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('success')
                        ->visible(fn (LaserQuote $record) => ! $record->order()->exists() && $record->status === QuoteStatus::ACCEPTED)
                        ->requiresConfirmation()
                        ->modalHeading('Transformer en commande')
                        ->modalDescription('Une commande sera créée avec les lignes du devis.')
                        ->action(function (LaserQuote $record) {
                             $order = app(LaserQuoteService::class)->convertToOrder($record);

                            Notification::make()
                                ->title('Commande créée : '.$order->reference)
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
