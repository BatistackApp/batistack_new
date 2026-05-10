<?php

namespace App\Filament\Resources\VatRates;

use App\Filament\Resources\VatRates\Pages\CreateVatRate;
use App\Filament\Resources\VatRates\Pages\EditVatRate;
use App\Filament\Resources\VatRates\Pages\ListVatRates;
use App\Filament\Resources\VatRates\Schemas\VatRateForm;
use App\Filament\Resources\VatRates\Tables\VatRatesTable;
use App\Models\Core\VatRate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VatRateResource extends Resource
{
    protected static ?string $model = VatRate::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Percent;

    protected static string|null|\UnitEnum $navigationGroup = 'Référentiels';

    protected static ?string $modelLabel = 'Taux Tva';
    protected static ?string $pluralModelLabel = 'Taux Tva';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return VatRateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VatRatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVatRates::route('/'),
            'create' => CreateVatRate::route('/create'),
            'edit' => EditVatRate::route('/{record}/edit'),
        ];
    }
}
