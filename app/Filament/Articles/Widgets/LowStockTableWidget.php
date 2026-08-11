<?php

namespace App\Filament\Articles\Widgets;

use App\Models\Articles\Stock;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class LowStockTableWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Articles sous le seuil critique';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Stock::whereColumn('quantity', '<=', 'min_threshold')
                    ->with(['item', 'warehouse'])
            )
            ->columns([
                TextColumn::make('item.reference')
                    ->label('Référence')
                    ->fontFamily('mono'),
                TextColumn::make('item.name')
                    ->label('Désignation')
                    ->limit(40),
                TextColumn::make('warehouse.name')
                    ->label('Dépôt'),
                TextColumn::make('quantity')->label('Quantité')
                    ->label('Stock')
                    ->numeric(decimalPlaces: 2)
                    ->color('danger')
                    ->suffix(fn ($record) => " {$record->item->unit->symbol}"),
                TextColumn::make('min_threshold')
                    ->label('Seuil mini')
                    ->numeric(decimalPlaces: 2)
                    ->color('gray'),
            ])
            ->recordActions([
                Action::make('view_item')
                    ->label('Voir la fiche')
                    ->icon(Phosphor::Eye)
                    ->url(fn ($record) => "/articles/items/{$record->item_id}"),
            ]);
    }
}
