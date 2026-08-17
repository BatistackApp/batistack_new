<?php

namespace App\Filament\Locations\Resources\Locations\InternalRentalInvoices;

use App\Filament\Locations\Resources\Locations\InternalRentalInvoices\Pages\CreateInternalRentalInvoice;
use App\Filament\Locations\Resources\Locations\InternalRentalInvoices\Pages\EditInternalRentalInvoice;
use App\Filament\Locations\Resources\Locations\InternalRentalInvoices\Pages\ListInternalRentalInvoices;
use App\Filament\Locations\Resources\Locations\InternalRentalInvoices\Schemas\InternalRentalInvoiceForm;
use App\Filament\Locations\Resources\Locations\InternalRentalInvoices\Tables\InternalRentalInvoicesTable;
use App\Models\Locations\InternalRentalInvoice;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InternalRentalInvoiceResource extends Resource
{
    protected static ?string $model = InternalRentalInvoice::class;

    protected static ?string $modelLabel = 'Facture interne de location';

    protected static ?string $pluralModelLabel = 'Factures internes (Refacturation)';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return InternalRentalInvoiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternalRentalInvoicesTable::configure($table);
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
            'index' => ListInternalRentalInvoices::route('/'),
            'create' => CreateInternalRentalInvoice::route('/create'),
            'edit' => EditInternalRentalInvoice::route('/{record}/edit'),
        ];
    }
}
