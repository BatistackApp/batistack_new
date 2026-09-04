<?php

namespace App\Filament\Articles\Resources\Store\Pages;

use App\Enums\Articles\ItemType;
use App\Enums\Articles\StockMouvementType;
use App\Filament\Articles\Resources\Store\StoreResource;
use App\Models\Articles\Item;
use App\Services\Articles\StoreService;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class StoreMovementHistory extends Page implements Tables\Contracts\HasTable
{
    use InteractsWithTable;

    protected static string $resource = StoreResource::class;

    protected static string|null|\BackedEnum $navigationIcon = Phosphor::ClockCounterClockwise;

    protected static string|null|\UnitEnum $navigationGroup = 'Magasin';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Historique des mouvements';

    protected string $view = 'filament.articles.resources.store.pages.store-movement-history';

    public ?int $item = null;

    public function mount(): void
    {
        $itemId = request()->query('item');

        if ($itemId) {
            $item = Item::find($itemId);
            $this->item = $item && $item->type === ItemType::STORE_ITEM ? $item->id : null;
        }
    }

    public function table(Table $table): Table
    {
        $storeId = $this->item;

        return $table
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
                Tables\Filters\Filter::make('date')
                    ->label('Plage de date')
                    ->schema([
                        DatePicker::make('start_date')->label('Du'),
                        DatePicker::make('end_date')->label('Au'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['start_date'], fn ($query, $date) => $query->where('created_at', '>=', $date))
                        ->when($data['end_date'], fn ($query, $date) => $query->where('created_at', '<=', $date))
                    )
            ]);
    }
}
