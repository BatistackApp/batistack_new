<?php

namespace App\Filament\Articles\Resources\InventoryCycles\Tables;

use App\Enums\Articles\InventoryCycleStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class InventoryCyclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse.name')
                    ->label('Dépôt')
                    ->searchable(),
                TextColumn::make('name')->label('Nom')
                    ->label('Nom du cycle')
                    ->searchable(),
                TextColumn::make('status')->label('Statut')
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
                TextColumn::make('created_at')->label('Créé le')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Mis à jour le')
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
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function (Collection $records, DeleteBulkAction $action) {
                            if ($records->contains(fn ($record) => $record->status === InventoryCycleStatus::COMPLETED)) {
                                Notification::make()->danger()->title('Impossible de supprimer des cycles complétés.')->send();
                                $action->failure();

                                return;
                            }
                            $records->each->delete();
                            Notification::make()->success()->title('Supprimé avec succès.')->send();
                        }),
                ]),
            ]);
    }
}
