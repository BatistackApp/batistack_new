<?php

namespace App\Filament\Locations\Resources\RentalContracts\Tables;

use App\Enums\Locations\RentalBillingPeriod;
use App\Enums\Locations\RentalStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class RentalContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->label('Référence'),

                TextColumn::make('chantier.name')
                    ->searchable()
                    ->sortable()
                    ->label('Chantier'),

                TextColumn::make('supplier.name')
                    ->searchable()
                    ->sortable()
                    ->label('Fournisseur'),

                TextColumn::make('status')->label('Statut')
                    ->badge()
                    ->sortable()
                    ->searchable()
                    ->label('Statut'),

                TextColumn::make('start_date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Date de début'),

                TextColumn::make('end_date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->label('Date de fin'),

                TextColumn::make('billing_period')
                    ->badge()
                    ->sortable()
                    ->label('Période facturation'),

                TextColumn::make('daily_cost_ht')
                    ->money('EUR')
                    ->sortable()
                    ->label('Coût journalier')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')
                    ->options(RentalStatus::class)
                    ->label('Statut'),

                SelectFilter::make('chantier_id')->label('Chantier')
                    ->relationship('chantier', 'name')
                    ->label('Chantier'),

                SelectFilter::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Fournisseur'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
