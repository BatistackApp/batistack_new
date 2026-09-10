<?php

namespace App\Filament\Laser\Resources\LaserQuotes\Tables;

use App\Enums\Laser\QuoteStatus;
use App\Models\Laser\LaserQuote;
use App\Services\Laser\LaserQuoteService;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;

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

                    Action::make('convertToOrder')
                        ->label('Transformer en commande')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('success')
                        ->visible(fn (LaserQuote $record) => ! $record->order()->exists() && in_array($record->status, [QuoteStatus::DRAFT, QuoteStatus::SENT, QuoteStatus::ACCEPTED]))
                        ->requiresConfirmation()
                        ->modalHeading('Transformer en commande')
                        ->modalDescription('Une commande sera créée avec les lignes du devis.')
                        ->action(function (LaserQuote $record) {
                            $order = app(LaserQuoteService::class)->acceptQuote($record);

                            Notification::make()
                                ->title('Commande créée : '.$order->reference)
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
