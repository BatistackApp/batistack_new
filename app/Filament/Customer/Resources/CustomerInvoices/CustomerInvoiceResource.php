<?php

namespace App\Filament\Customer\Resources\CustomerInvoices;

use App\Filament\Customer\Resources\CustomerInvoices\Pages\ListCustomerInvoices;
use App\Filament\Customer\Resources\CustomerInvoices\Pages\ViewCustomerInvoice;
use App\Filament\Customer\Resources\CustomerInvoices\Schemas\CustomerInvoiceInfolist;
use App\Filament\Customer\Resources\CustomerInvoices\Tables\CustomerInvoicesTable;
use App\Models\Commerce\CustomerInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerInvoiceResource extends Resource
{
    protected static ?string $model = CustomerInvoice::class;

    protected static string|null|BackedEnum $navigationIcon = Phosphor::Receipt;

    protected static ?string $navigationLabel = 'Mes Factures';

    protected static ?string $modelLabel = 'Facture';

    protected static ?string $pluralModelLabel = 'Factures';

    protected static ?int $navigationSort = 4;

    protected static string|null|\UnitEnum $navigationGroup = 'Mes Achats et Prestations';

    protected static ?string $recordTitleAttribute = 'reference';

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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerInvoices::route('/'),
            'view' => ViewCustomerInvoice::route('/{record}'),
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
