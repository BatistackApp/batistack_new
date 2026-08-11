<?php

namespace App\Filament\Articles\Resources\Warehouses\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Inventaire du dépôt';

    protected static string|null|\BackedEnum $icon = Phosphor::Package;

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.reference')
            ->columns([
                TextColumn::make('item.reference')
                    ->label('Réf.')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('item.name')
                    ->label('Article')
                    ->searchable(),
                TextColumn::make('quantity')->label('Quantité')
                    ->label('Stock actuel')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn ($record) => $record->quantity <= $record->min_threshold ? 'danger' : 'success')
                    ->suffix(fn ($record) => " {$record->item->unit->symbol}"),
            ])
            ->headerActions([
            ])
            ->recordActions([
                EditAction::make()->label('Ajuster seuil'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
