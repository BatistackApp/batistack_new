<?php

namespace App\Filament\Customer\Resources\CustomerOrders;

use App\Filament\Customer\Resources\CustomerOrders\Pages\ListCustomerOrders;
use App\Filament\Customer\Resources\CustomerOrders\Pages\ViewCustomerOrder;
use App\Filament\Customer\Resources\CustomerOrders\RelationManagers\ItemsRelationManager;
use App\Filament\Customer\Resources\CustomerOrders\Schemas\CustomerOrderInfolist;
use App\Filament\Customer\Resources\CustomerOrders\Tables\CustomerOrdersTable;
use App\Models\Commerce\CustomerOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerOrderResource extends Resource
{
    protected static ?string $model = CustomerOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::ShoppingBag;

    protected static ?string $navigationLabel = 'Mes Commandes';

    protected static ?string $modelLabel = 'Commande';

    protected static ?string $pluralModelLabel = 'Commandes';

    protected static ?int $navigationSort = 2;

    protected static string|null|\UnitEnum $navigationGroup = 'Mes Achats et Prestations';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function infolist(Schema $schema): Schema
    {
        return CustomerOrderInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerOrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerOrders::route('/'),
            'view' => ViewCustomerOrder::route('/{record}'),
        ];
    }

    public static function canAccess(): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        return true;
    }
}
