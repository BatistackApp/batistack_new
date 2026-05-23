<?php

namespace App\Filament\Customer\Resources\CustomerQuotes;

use App\Filament\Customer\Resources\CustomerQuotes\Pages\CreateCustomerQuote;
use App\Filament\Customer\Resources\CustomerQuotes\Pages\EditCustomerQuote;
use App\Filament\Customer\Resources\CustomerQuotes\Pages\ListCustomerQuotes;
use App\Filament\Customer\Resources\CustomerQuotes\Pages\ViewCustomerQuote;
use App\Filament\Customer\Resources\CustomerQuotes\Schemas\CustomerQuoteForm;
use App\Filament\Customer\Resources\CustomerQuotes\Schemas\CustomerQuoteInfolist;
use App\Filament\Customer\Resources\CustomerQuotes\Tables\CustomerQuotesTable;
use App\Models\Commerce\CustomerQuote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerQuoteResource extends Resource
{
    protected static ?string $model = CustomerQuote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return CustomerQuoteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerQuoteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerQuotesTable::configure($table);
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
            'index' => ListCustomerQuotes::route('/'),
            'create' => CreateCustomerQuote::route('/create'),
            'view' => ViewCustomerQuote::route('/{record}'),
            'edit' => EditCustomerQuote::route('/{record}/edit'),
        ];
    }
}
