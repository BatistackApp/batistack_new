<?php

namespace App\Filament\Laser\Resources\LaserOrders;

use App\Filament\Laser\Resources\LaserOrders\Pages\CreateLaserOrder;
use App\Filament\Laser\Resources\LaserOrders\Pages\ListLaserOrders;
use App\Filament\Laser\Resources\LaserOrders\Pages\ViewLaserOrder;
use App\Filament\Laser\Resources\LaserOrders\RelationManagers\LinesRelationManager;
use App\Filament\Laser\Resources\LaserOrders\Schemas\LaserOrderForm;
use App\Filament\Laser\Resources\LaserOrders\Tables\LaserOrdersTable;
use App\Models\Laser\LaserOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class LaserOrderResource extends Resource
{
    protected static ?string $model = LaserOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::ShoppingBag;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?string $navigationLabel = 'Commandes';

    protected static ?string $modelLabel = 'Commande Laser';

    protected static ?string $pluralModelLabel = 'Commandes Laser';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('client');
    }

    public static function form(Schema $schema): Schema
    {
        return LaserOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaserOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLaserOrders::route('/'),
            'view' => ViewLaserOrder::route('/{record}'),
        ];
    }
}
