<?php

namespace App\Filament\Customer\Resources\CustomerQuotes;

use App\Filament\Customer\Resources\CustomerQuotes\Pages\ListCustomerQuotes;
use App\Filament\Customer\Resources\CustomerQuotes\Pages\ViewCustomerQuote;
use App\Filament\Customer\Resources\CustomerQuotes\RelationManagers\ItemsRelationManager;
use App\Filament\Customer\Resources\CustomerQuotes\Schemas\CustomerQuoteInfolist;
use App\Filament\Customer\Resources\CustomerQuotes\Tables\CustomerQuotesTable;
use App\Models\Commerce\CustomerQuote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerQuoteResource extends Resource
{
    protected static ?string $model = CustomerQuote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::File;

    protected static ?string $navigationLabel = 'Mes Devis';
    protected static ?string $modelLabel = 'Devis';
    protected static ?string $pluralModelLabel = 'Devis';

    protected static ?string $recordTitleAttribute = 'reference';

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
            'view' => ViewCustomerQuote::route('/{record}'),
        ];
    }
}
