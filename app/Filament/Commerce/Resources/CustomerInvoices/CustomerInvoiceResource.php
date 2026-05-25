<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices;

use App\Filament\Commerce\Resources\CustomerInvoices\Pages\CreateCustomerInvoice;
use App\Filament\Commerce\Resources\CustomerInvoices\Pages\EditCustomerInvoice;
use App\Filament\Commerce\Resources\CustomerInvoices\Pages\ListCustomerInvoices;
use App\Filament\Commerce\Resources\CustomerInvoices\Pages\ViewCustomerInvoice;
use App\Filament\Commerce\Resources\CustomerInvoices\Schemas\CustomerInvoiceForm;
use App\Filament\Commerce\Resources\CustomerInvoices\Schemas\CustomerInvoiceInfolist;
use App\Filament\Commerce\Resources\CustomerInvoices\Tables\CustomerInvoicesTable;
use App\Models\Commerce\CustomerInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class CustomerInvoiceResource extends Resource
{
    protected static ?string $model = CustomerInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Receipt;
    protected static ?string $navigationLabel = 'Factures client';
    protected static string | UnitEnum | null $navigationGroup = 'Ventes';
    protected static ?string $modelLabel = 'Facture';
    protected static ?string $pluralModelLabel = 'Factures';
    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return CustomerInvoiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInvoiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerInvoicesTable::configure($table);
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
            'index' => ListCustomerInvoices::route('/'),
            'create' => CreateCustomerInvoice::route('/create'),
            'view' => ViewCustomerInvoice::route('/{record}'),
            'edit' => EditCustomerInvoice::route('/{record}/edit'),
        ];
    }
}
