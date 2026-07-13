<?php

namespace App\Filament\Commerce\Resources\CustomerSituations;

use App\Filament\Commerce\Resources\CustomerSituations\Pages\CreateCustomerSituation;
use App\Filament\Commerce\Resources\CustomerSituations\Pages\EditCustomerSituation;
use App\Filament\Commerce\Resources\CustomerSituations\Pages\ListCustomerSituations;
use App\Filament\Commerce\Resources\CustomerSituations\Schemas\CustomerSituationForm;
use App\Filament\Commerce\Resources\CustomerSituations\Tables\CustomerSituationsTable;
use App\Models\CustomerSituation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerSituationResource extends Resource
{
    protected static ?string $model = CustomerSituation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return CustomerSituationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerSituationsTable::configure($table);
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
            'index' => ListCustomerSituations::route('/'),
            'create' => CreateCustomerSituation::route('/create'),
            'edit' => EditCustomerSituation::route('/{record}/edit'),
        ];
    }
}
