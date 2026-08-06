<?php

namespace App\Filament\Articles\Resources\InventoryCycles;

use App\Filament\Articles\Resources\InventoryCycles\Pages\CreateInventoryCycle;
use App\Filament\Articles\Resources\InventoryCycles\Pages\EditInventoryCycle;
use App\Filament\Articles\Resources\InventoryCycles\Pages\ListInventoryCycles;
use App\Filament\Articles\Resources\InventoryCycles\Pages\ViewInventoryCycle;
use App\Filament\Articles\Resources\InventoryCycles\Schemas\InventoryCycleForm;
use App\Filament\Articles\Resources\InventoryCycles\Schemas\InventoryCycleInfolist;
use App\Filament\Articles\Resources\InventoryCycles\Tables\InventoryCyclesTable;
use App\Models\Articles\InventoryCycle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InventoryCycleResource extends Resource
{
    protected static ?string $model = InventoryCycle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $modelLabel = 'Inventaire tournant';

    protected static ?string $pluralModelLabel = 'Inventaires tournants';

    protected static string|null|\UnitEnum $navigationGroup = 'Logistique';

    public static function form(Schema $schema): Schema
    {
        return InventoryCycleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InventoryCycleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryCyclesTable::configure($table);
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
            'index' => ListInventoryCycles::route('/'),
            'create' => CreateInventoryCycle::route('/create'),
            'view' => ViewInventoryCycle::route('/{record}'),
            'edit' => EditInventoryCycle::route('/{record}/edit'),
        ];
    }
}
