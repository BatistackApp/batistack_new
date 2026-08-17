<?php

namespace App\Filament\Locations\Resources\Locations\InternalRentalInvoices\Tables;

use App\Enums\Locations\InternalRentalInvoiceStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InternalRentalInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fixedAsset.name')
                    ->label('Actif / Machine')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('period_start')
                    ->label('Période')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('period_end')
                    ->label('Fin période')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('days')
                    ->label('Jours')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('daily_rate')
                    ->label('Tarif / jour')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('amount_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('billing_key')
                    ->label('Clé')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(InternalRentalInvoiceStatus::class),
                SelectFilter::make('fixed_asset_id')
                    ->label('Actif / Machine')
                    ->relationship('fixedAsset', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('period_start', 'desc');
    }
}
