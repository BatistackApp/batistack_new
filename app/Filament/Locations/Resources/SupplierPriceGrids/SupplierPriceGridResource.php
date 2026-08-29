<?php

namespace App\Filament\Locations\Resources\SupplierPriceGrids;

use App\Filament\Locations\Resources\SupplierPriceGrids\Pages\CreateSupplierPriceGrid;
use App\Filament\Locations\Resources\SupplierPriceGrids\Pages\EditSupplierPriceGrid;
use App\Filament\Locations\Resources\SupplierPriceGrids\Pages\ListSupplierPriceGrids;
use App\Filament\Locations\Resources\SupplierPriceGrids\Schemas\SupplierPriceGridForm;
use App\Filament\Locations\Resources\SupplierPriceGrids\Tables\SupplierPriceGridsTable;
use App\Models\Locations\SupplierPriceGrid;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierPriceGridResource extends Resource
{
    protected static ?string $model = SupplierPriceGrid::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'equipment_category';

    public static function form(Schema $schema): Schema
    {
        return SupplierPriceGridForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierPriceGridsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupplierPriceGrids::route('/'),
            'create' => CreateSupplierPriceGrid::route('/create'),
            'edit' => EditSupplierPriceGrid::route('/{record}/edit'),
        ];
    }
}
