<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryCyclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Dépôt')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nom du cycle')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->searchable(),
                TextColumn::make('creator.name')
                    ->label('Créé par')
                    ->sortable(),
                TextColumn::make('approver.name')
                    ->label('Approuvé par')
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Date d\'approbation')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, \Filament\Tables\Actions\DeleteBulkAction $action) {
                            if ($records->contains(fn($record) => $record->status === \App\Enums\Articles\InventoryCycleStatus::COMPLETED)) {
                                \Filament\Notifications\Notification::make()->danger()->title('Impossible de supprimer des cycles complétés.')->send();
                                $action->failure();
                                return;
                            }
                            $records->each->delete();
                            \Filament\Notifications\Notification::make()->success()->title('Supprimé avec succès.')->send();
                        }),
                ]),
            ]);
    }
}
