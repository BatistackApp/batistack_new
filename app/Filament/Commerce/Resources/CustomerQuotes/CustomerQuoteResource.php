<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes;

use App\Filament\Commerce\Resources\CustomerQuotes\Pages\CreateCustomerQuote;
use App\Filament\Commerce\Resources\CustomerQuotes\Pages\EditCustomerQuote;
use App\Filament\Commerce\Resources\CustomerQuotes\Pages\ListCustomerQuotes;
use App\Filament\Commerce\Resources\CustomerQuotes\Pages\ViewCustomerQuote;
use App\Filament\Commerce\Resources\CustomerQuotes\RelationManagers\ItemsRelationManager;
use App\Filament\Commerce\Resources\CustomerQuotes\Schemas\CustomerQuoteForm;
use App\Filament\Commerce\Resources\CustomerQuotes\Schemas\CustomerQuoteInfolist;
use App\Filament\Commerce\Resources\CustomerQuotes\Tables\CustomerQuotesTable;
use App\Models\Commerce\CustomerQuote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class CustomerQuoteResource extends Resource
{
    protected static ?string $model = CustomerQuote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::FileText;

    protected static string|UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?string $navigationLabel = 'Devis';

    protected static ?string $modelLabel = 'Devis';

    protected static ?string $pluralModelLabel = 'Devis';

    protected static ?int $navigationSort = 1;

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
            ItemsRelationManager::class,
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
