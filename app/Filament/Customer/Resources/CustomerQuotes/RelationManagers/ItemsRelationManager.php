<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Produits/Services';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.reference')
            ->groups([
                Group::make('lot_label')
                    ->collapsible(),
            ])
            ->defaultGroup('lot_label')
            ->columns([
                TextColumn::make('item.reference')
                    ->label('#'),

                TextColumn::make('item.name')
                    ->label('Désignation')
                    ->description(fn (Model $record) => $record->item->description),

                TextColumn::make('selling_price')
                    ->label('Prix Unitaire HT')
                    ->money('EUR'),

                TextColumn::make('quantity')->label('Quantité')
                    ->label('Qte.')
                    ->numeric(),

                TextColumn::make('item.unit.symbol')
                    ->label('Unité'),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR'),

                TextColumn::make('vatRate.rate')
                    ->label('TVA')
                    ->numeric()
                    ->suffix('%'),
            ])
            ->filters([]); // Customer portal is read-only: no write actions are registered.
    }
}
