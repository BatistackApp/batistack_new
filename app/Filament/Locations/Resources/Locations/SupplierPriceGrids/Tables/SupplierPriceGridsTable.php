<?php

namespace App\Filament\Locations\Resources\Locations\SupplierPriceGrids\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierPriceGridsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('equipment_category')
                    ->label('Équipement')
                    ->searchable(),
                TextColumn::make('daily_rate')
                    ->label('Jour')
                    ->money('eur')
                    ->sortable(),
                TextColumn::make('weekly_rate')
                    ->label('Semaine')
                    ->money('eur')
                    ->sortable(),
                TextColumn::make('monthly_rate')
                    ->label('Mois')
                    ->money('eur')
                    ->sortable(),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
