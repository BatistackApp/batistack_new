<?php

namespace App\Filament\Articles\Resources\Warehouses;

use App\Filament\Articles\Resources\Warehouses\Pages\CreateWarehouse;
use App\Filament\Articles\Resources\Warehouses\Pages\EditWarehouse;
use App\Filament\Articles\Resources\Warehouses\Pages\ListWarehouses;
use App\Filament\Articles\Resources\Warehouses\RelationManagers\StocksRelationManager;
use App\Filament\Articles\Resources\Warehouses\Schemas\WarehouseForm;
use App\Filament\Articles\Resources\Warehouses\Tables\WarehousesTable;
use App\Models\Articles\Warehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Warehouse;
    protected static ?string $navigationLabel = 'Dépôts';
    protected static ?string $modelLabel = 'Dépôt';
    protected static ?string $pluralModelLabel = 'Dépôts';

    protected static string|null|\UnitEnum $navigationGroup = 'Configuration Dépôts';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return WarehouseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehousesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            StocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouses::route('/'),
            'create' => CreateWarehouse::route('/create'),
            'edit' => EditWarehouse::route('/{record}/edit'),
        ];
    }
}
