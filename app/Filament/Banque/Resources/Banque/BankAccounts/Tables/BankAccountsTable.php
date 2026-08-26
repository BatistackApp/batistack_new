<?php

namespace App\Filament\Banque\Resources\Banque\BankAccounts\Tables;

use App\Jobs\Banque\SyncBridgeTransactionsJob;
use App\Models\Banque\BankAccount;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')
                    ->label('Nom du compte')
                    ->searchable(),
                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('iban')
                    ->label('IBAN')
                    ->searchable(),
                TextColumn::make('bic')
                    ->label('BIC')
                    ->searchable(),
                TextColumn::make('currency')
                    ->label('Devise')
                    ->searchable(),
                TextColumn::make('balance')
                    ->label('Solde')
                    ->money(
                        currency: 'eur',
                        locale: 'fr',
                    )
                    ->sortable(),

                TextColumn::make('created_at')->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('sync')
                    ->label('Synchroniser Bridge')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (BankAccount $record) {
                        SyncBridgeTransactionsJob::dispatch($record, auth()->id());

                        Notification::make()
                            ->title('Synchronisation lancée')
                            ->body("Le téléchargement de l'historique des transactions est en cours en arrière-plan. Vous serez notifié une fois terminé.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
