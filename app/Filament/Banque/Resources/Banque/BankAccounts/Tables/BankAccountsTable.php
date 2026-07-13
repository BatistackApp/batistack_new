<?php

namespace App\Filament\Banque\Resources\Banque\BankAccounts\Tables;

use App\Models\Banque\BankAccount;
use App\Services\Banque\BankinApiService;
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
                TextColumn::make('company.id')
                    ->label('Société')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nom du compte')
                    ->searchable(),
                TextColumn::make('type')
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
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bankin_item_id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('sync')
                    ->label('Synchroniser Bankin')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (BankAccount $record) {
                        $service = new BankinApiService;
                        $imported = $service->syncTransactions($record);

                        Notification::make()
                            ->title('Synchronisation réussie')
                            ->body("{$imported} transactions importées.")
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
