<?php

namespace App\Filament\Commerce\Resources\CustomerCreditNotes;

use App\Filament\Commerce\Resources\CustomerCreditNotes\Pages\CreateCustomerCreditNote;
use App\Filament\Commerce\Resources\CustomerCreditNotes\Pages\EditCustomerCreditNote;
use App\Filament\Commerce\Resources\CustomerCreditNotes\Pages\ListCustomerCreditNotes;
use App\Filament\Commerce\Resources\CustomerCreditNotes\Schemas\CustomerCreditNoteForm;
use App\Filament\Commerce\Resources\CustomerCreditNotes\Tables\CustomerCreditNotesTable;
use App\Models\Commerce\CustomerCreditNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class CustomerCreditNoteResource extends Resource
{
    protected static ?string $model = CustomerCreditNote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Receipt;

    protected static ?string $navigationLabel = 'Avoirs Clients';

    protected static string|\UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?string $modelLabel = 'Avoir Client';

    protected static ?string $pluralModelLabel = 'Avoirs Clients';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return CustomerCreditNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerCreditNotesTable::configure($table);
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
            'index' => ListCustomerCreditNotes::route('/'),
            'create' => CreateCustomerCreditNote::route('/create'),
            'edit' => EditCustomerCreditNote::route('/{record}/edit'),
        ];
    }
}
