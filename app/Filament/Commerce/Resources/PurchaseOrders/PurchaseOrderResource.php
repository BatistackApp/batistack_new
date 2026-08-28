<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders;

use App\Filament\Commerce\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Commerce\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Commerce\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Commerce\Resources\PurchaseOrders\RelationManagers\ItemsRelationManager;
use App\Filament\Commerce\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Commerce\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\Commerce\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::ShoppingCart;

    protected static ?string $navigationLabel = 'Commandes Fournisseurs';

    protected static string|\UnitEnum|null $navigationGroup = 'Achats';

    protected static ?string $modelLabel = 'Commande Fournisseur';

    protected static ?string $pluralModelLabel = 'Commandes Fournisseurs';

    public static function form(Schema $schema): Schema
    {
        return PurchaseOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PurchaseOrdersTable::configure($table);
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
            'index' => ListPurchaseOrders::route('/'),
            'create' => CreatePurchaseOrder::route('/create'),
            'edit' => EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
