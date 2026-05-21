<?php

namespace App\Filament\Commerce\Resources\SupplierInvoices;

use App\Filament\Commerce\Resources\SupplierInvoices\Pages\CreateSupplierInvoice;
use App\Filament\Commerce\Resources\SupplierInvoices\Pages\EditSupplierInvoice;
use App\Filament\Commerce\Resources\SupplierInvoices\Pages\ListSupplierInvoices;
use App\Filament\Commerce\Resources\SupplierInvoices\Pages\ViewSupplierInvoice;
use App\Filament\Commerce\Resources\SupplierInvoices\Schemas\SupplierInvoiceForm;
use App\Filament\Commerce\Resources\SupplierInvoices\Schemas\SupplierInvoiceInfolist;
use App\Filament\Commerce\Resources\SupplierInvoices\Tables\SupplierInvoicesTable;
use App\Models\Commerce\SupplierInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SupplierInvoiceResource extends Resource
{
    protected static ?string $model = SupplierInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::FileMinus;
    protected static ?string $navigationLabel = 'Factures fournisseur';
    protected static ?string $modelLabel = 'Facture fournisseur';
    protected static ?string $pluralModelLabel = 'Factures Fournisseur';
    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return SupplierInvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SupplierInvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierInvoicesTable::configure($table);
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
            'index' => ListSupplierInvoices::route('/'),
            'create' => CreateSupplierInvoice::route('/create'),
            'view' => ViewSupplierInvoice::route('/{record}'),
            'edit' => EditSupplierInvoice::route('/{record}/edit'),
        ];
    }
}
