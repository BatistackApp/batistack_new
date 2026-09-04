<?php

namespace App\Filament\Articles\Resources\Store\Pages;

use App\Enums\Articles\StockMouvementType;
use App\Filament\Articles\Resources\Store\StoreResource;
use App\Services\Articles\StoreService;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\DatePickerFilter;
use Filament\Tables\Table as TableBuilder;

class StoreMovementHistory extends Page
{
    protected static string $resource = StoreResource::class;

    protected static ?string $title = 'Historique des mouvements';

    public ?int $item = null;

    public function mount(): void
    {
        $this->item = request()->query('item');
    }

    public function getTable(): TableBuilder
    {
        $storeId = $this->item;

        return TableBuilder::make()
            ->query(
                app(StoreService::class)->getMovementHistory(
                    itemId: $storeId ? (int) $storeId : null,
                )
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('stock.item.name')
                    ->label('Article')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('stock.item.reference')
                    ->label('Référence')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === StockMouvementType::IN->value ? 'Entrée' : 'Sortie')
                    ->color(fn ($state) => $state === StockMouvementType::IN->value ? 'success' : 'warning'),
                TextColumn::make('quantity_delta')
                    ->label('Quantité')
                    ->numeric()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->prefix(fn ($state) => $state > 0 ? '+' : ''),
                TextColumn::make('quantity_after')
                    ->label('Stock après')
                    ->numeric(),
                TextColumn::make('reason')
                    ->label('Motif')
                    ->limit(50),
                TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                DatePickerFilter::make('start_date')
                    ->label('Du')
                    ->query(fn ($query, $value) => $query->where('created_at', '>=', $value)),
                DatePickerFilter::make('end_date')
                    ->label('Au')
                    ->query(fn ($query, $value) => $query->where('created_at', '<=', $value)),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
