<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders;

use App\Filament\Commerce\Resources\PurchaseOrders\Pages\CreatePurchaseOrder;
use App\Filament\Commerce\Resources\PurchaseOrders\Pages\EditPurchaseOrder;
use App\Filament\Commerce\Resources\PurchaseOrders\Pages\ListPurchaseOrders;
use App\Filament\Commerce\Resources\PurchaseOrders\Schemas\PurchaseOrderForm;
use App\Filament\Commerce\Resources\PurchaseOrders\Tables\PurchaseOrdersTable;
use App\Models\PurchaseOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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
            //
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
