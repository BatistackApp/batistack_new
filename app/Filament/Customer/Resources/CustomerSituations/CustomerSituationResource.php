<?php

namespace App\Filament\Customer\Resources\CustomerSituations;

use App\Filament\Customer\Concerns\ScopesToAuthenticatedThirdParty;
use App\Filament\Customer\Resources\CustomerSituations\Pages\ListCustomerSituations;
use App\Filament\Customer\Resources\CustomerSituations\Pages\ViewCustomerSituation;
use App\Filament\Customer\Resources\CustomerSituations\Tables\CustomerSituationsTable;
use App\Models\Commerce\CustomerSituation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerSituationResource extends Resource
{
    use ScopesToAuthenticatedThirdParty;

    protected static ?string $model = CustomerSituation::class;

    protected static string|null|BackedEnum $navigationIcon = Phosphor::ChartLineDown;

    protected static ?string $navigationLabel = 'Situations d\'avancement';

    protected static ?string $modelLabel = 'Situation';

    protected static ?string $pluralModelLabel = 'Situations';

    protected static ?int $navigationSort = 5;

    protected static string|null|\UnitEnum $navigationGroup = 'Mes Achats et Prestations';

    protected static ?string $recordTitleAttribute = 'number';

    public static function table(Table $table): Table
    {
        return CustomerSituationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerSituations::route('/'),
            'view' => ViewCustomerSituation::route('/{record}'),
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
