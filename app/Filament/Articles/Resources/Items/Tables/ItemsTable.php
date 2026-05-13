<?php

namespace App\Filament\Articles\Resources\Items\Tables;

use App\Enums\Articles\ItemType;
use App\Models\Articles\Item;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->circular(),

                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('name')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),

                TextColumn::make('purchase_price')
                    ->label('Coût / PUMP')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('selling_price')
                    ->label('PV HT')
                    ->money('EUR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('stocks_sum_quantity')
                    ->label('Stock Global')
                    ->sum('stocks', 'quantity')
                    ->suffix(fn (Item $record) => " {$record->unit->symbol}")
                    ->color(fn ($state, Item $record) => $state <= 0 ? 'danger' : 'success')
                    ->visible(fn ($record) => $record?->type === ItemType::STOCKABLE),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(ItemType::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
