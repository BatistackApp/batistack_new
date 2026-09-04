<?php

namespace App\Filament\Articles\Resources\Store;

use App\Filament\Articles\Resources\Store\Pages\ListStoreItems;
use App\Filament\Articles\Resources\Store\Pages\StoreMovementHistory;
use App\Models\Articles\Item;
use BackedEnum;
use Filament\Resources\Resource;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class StoreResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::ShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Magasin';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Consommable Magasin';

    protected static ?string $pluralModelLabel = 'Magasin';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getPages(): array
    {
        return [
            'index' => ListStoreItems::route('/'),
            'history' => StoreMovementHistory::route('/history'),
        ];
    }
}
