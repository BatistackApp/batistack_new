<?php

namespace App\Filament\Commerce\Resources\CustomerOrders;

use App\Filament\Commerce\Resources\CustomerOrders\Pages\CreateCustomerOrder;
use App\Filament\Commerce\Resources\CustomerOrders\Pages\EditCustomerOrder;
use App\Filament\Commerce\Resources\CustomerOrders\Pages\ListCustomerOrders;
use App\Filament\Commerce\Resources\CustomerOrders\Pages\ViewCustomerOrder;
use App\Filament\Commerce\Resources\CustomerOrders\RelationManagers\DeliveryNotesRelationManager;
use App\Filament\Commerce\Resources\CustomerOrders\RelationManagers\InvoicesRelationManager;
use App\Filament\Commerce\Resources\CustomerOrders\RelationManagers\ItemsRelationManager;
use App\Filament\Commerce\Resources\CustomerOrders\RelationManagers\ManufacturingOrdersRelationManager;
use App\Filament\Commerce\Resources\CustomerOrders\RelationManagers\SituationsRelationManager;
use App\Filament\Commerce\Resources\CustomerOrders\Schemas\CustomerOrderForm;
use App\Filament\Commerce\Resources\CustomerOrders\Schemas\CustomerOrderInfolist;
use App\Filament\Commerce\Resources\CustomerOrders\Tables\CustomerOrdersTable;
use App\Models\Commerce\CustomerOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerOrderResource extends Resource
{
    protected static ?string $model = CustomerOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::ShoppingBag;

    protected static string|null|\UnitEnum $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Bon de Commandes';

    protected static ?string $modelLabel = 'Commande';

    protected static ?string $pluralModelLabel = 'Commandes';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return CustomerOrderForm::configure($schema);
    }

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
            DeliveryNotesRelationManager::class,
            SituationsRelationManager::class,
            InvoicesRelationManager::class,
            ManufacturingOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerOrders::route('/'),
            'create' => CreateCustomerOrder::route('/create'),
            'view' => ViewCustomerOrder::route('/{record}'),
            'edit' => EditCustomerOrder::route('/{record}/edit'),
        ];
    }
}
