<?php

namespace App\Filament\Gpao\Gpao\Machines\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MachinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')
                    ->searchable(),
                TextColumn::make('reference')->label('Référence')
                    ->searchable(),
                TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->searchable(),
                TextColumn::make('usage_hours')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('maintenance_interval_hours')
                    ->numeric()
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
