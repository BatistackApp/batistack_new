<?php

namespace App\Filament\Articles\Widgets;

use App\Enums\Articles\StockMouvementType;
use App\Models\Articles\StockMouvement;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestStockMouvementsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Derniers Mouvements de Stock';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockMouvement::query()
                    ->with(['stock.item', 'stock.warehouse', 'stock.locations', 'user'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Créé le')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock.item.name')
                    ->label('Article')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stock.warehouse.name')
                    ->label('Entrepôt'),
                Tables\Columns\BadgeColumn::make('type')->label('Type')
                    ->label('Type')
                    ->colors([
                        'success' => StockMouvementType::IN,
                        'danger' => StockMouvementType::OUT,
                    ])
                    ->formatStateUsing(fn (StockMouvementType $state): string => match ($state) {
                        StockMouvementType::IN => 'Entrée',
                        StockMouvementType::OUT => 'Sortie',
                    }),
                Tables\Columns\TextColumn::make('quantity_delta')
                    ->label('Quantité')
                    ->numeric()
                    ->color(fn (StockMouvement $record): string => $record->isIncoming() ? 'success' : 'danger')
                    ->formatStateUsing(fn (StockMouvement $record): string => ($record->isIncoming() ? '+' : '-').' '.$record->quantity_delta),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->default('Système'),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Source')
                    ->formatStateUsing(fn (StockMouvement $record): string => $record->getSourceLabel()),
                Tables\Columns\TextColumn::make('stock.locations_summary')
                    ->label('Emplacement')
                    ->state(fn ($record) => $record->stock->locations->pluck('location_code')->filter()->implode(', ') ?: '—')
                    ->badge()
                    ->color('info'),
            ])
            ->paginated(false);
    }
}
