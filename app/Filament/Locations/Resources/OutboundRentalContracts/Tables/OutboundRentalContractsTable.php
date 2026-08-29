<?php

namespace App\Filament\Locations\Resources\OutboundRentalContracts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OutboundRentalContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('third_party_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('chantier_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reference')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('billing_period')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('expected_end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('actual_end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('daily_penalty_rate')
                    ->numeric()
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
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
