<?php

namespace App\Filament\Customer\Resources\CustomerCreditNotes;

use App\Filament\Customer\Concerns\ScopesToAuthenticatedThirdParty;
use App\Filament\Customer\Resources\CustomerCreditNotes\Pages\ListCustomerCreditNotes;
use App\Filament\Customer\Resources\CustomerCreditNotes\Pages\ViewCustomerCreditNote;
use App\Filament\Customer\Resources\CustomerCreditNotes\Tables\CustomerCreditNotesTable;
use App\Models\Commerce\CustomerCreditNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerCreditNoteResource extends Resource
{
    use ScopesToAuthenticatedThirdParty;

    protected static ?string $model = CustomerCreditNote::class;

    protected static string|null|BackedEnum $navigationIcon = Phosphor::CurrencyCircleDollar;

    protected static ?string $navigationLabel = 'Mes Avoirs';

    protected static ?string $modelLabel = 'Avoir';

    protected static ?string $pluralModelLabel = 'Avoirs';

    protected static ?int $navigationSort = 6;

    protected static string|null|\UnitEnum $navigationGroup = 'Mes Achats et Prestations';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function table(Table $table): Table
    {
        return CustomerCreditNotesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomerCreditNotes::route('/'),
            'view' => ViewCustomerCreditNote::route('/{record}'),
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
