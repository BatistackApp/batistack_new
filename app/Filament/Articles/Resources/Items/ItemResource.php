<?php

namespace App\Filament\Articles\Resources\Items;

use App\Filament\Articles\Resources\Items\Pages\CreateItem;
use App\Filament\Articles\Resources\Items\Pages\EditItem;
use App\Filament\Articles\Resources\Items\Pages\ListItems;
use App\Filament\Articles\Resources\Items\Pages\ViewItem;
use App\Filament\Articles\Resources\Items\RelationManagers\ComponentsRelationManager;
use App\Filament\Articles\Resources\Items\RelationManagers\StocksRelationManager;
use App\Filament\Articles\Resources\Items\Schemas\ItemForm;
use App\Filament\Articles\Resources\Items\Schemas\ItemInfolist;
use App\Filament\Articles\Resources\Items\Tables\ItemsTable;
use App\Models\Articles\Item;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class ItemResource extends Resource
{
    protected static ?string $model = Item::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Package;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $modelLabel = 'Article / Ouvrage';

    protected static ?string $pluralModelLabel = 'Catalogue';

    public static function form(Schema $schema): Schema
    {
        return ItemForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ItemInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ComponentsRelationManager::class,
            StocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListItems::route('/'),
            'create' => CreateItem::route('/create'),
            'view' => ViewItem::route('/{record}'),
            'edit' => EditItem::route('/{record}/edit'),
        ];
    }
}
