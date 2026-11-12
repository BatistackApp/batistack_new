<?php

namespace App\Filament\Commerce\Resources\CustomerCreditNotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CustomerCreditNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.reference')
                    ->label('Facture')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            if (! $records->every(fn ($r) => $r->canBeDeleted())) {
                                Notification::make()
                                    ->danger()
                                    ->title('Suppression impossible')
                                    ->body('Seuls les documents en brouillon peuvent être supprimés.')
                                    ->send();

                                return;
                            }

                            $records->each->delete();

                            Notification::make()
                                ->success()
                                ->title('Avoirs supprimés')
                                ->send();
                        }),
                ]),
            ]);
    }
}
