<?php

namespace App\Filament\Customer\Resources\CustomerOrders\RelationManagers;

use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Articles / Services';

    protected static string|BackedEnum|null $icon = Phosphor::Package;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Désignation'),

                TextColumn::make('quantity')
                    ->label('Qte')
                    ->numeric(),

                TextColumn::make('item.unit.name')
                    ->label('Unité'),

                TextColumn::make('selling_price')
                    ->label('Prix Unitaire HT')
                    ->money('EUR'),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR')
                    ->summarize(Sum::make('total_ht')->money('EUR')->hiddenLabel()),

                TextColumn::make('vatRate.rate')
                    ->label('TVA')
                    ->numeric()
                    ->suffix('%'),
            ]);
    }
}
