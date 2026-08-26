<?php

namespace App\Filament\Commerce\Resources\SupplierCreditNotes;

use App\Filament\Commerce\Resources\SupplierCreditNotes\Pages\CreateSupplierCreditNote;
use App\Filament\Commerce\Resources\SupplierCreditNotes\Pages\EditSupplierCreditNote;
use App\Filament\Commerce\Resources\SupplierCreditNotes\Pages\ListSupplierCreditNotes;
use App\Filament\Commerce\Resources\SupplierCreditNotes\Schemas\SupplierCreditNoteForm;
use App\Filament\Commerce\Resources\SupplierCreditNotes\Tables\SupplierCreditNotesTable;
use App\Models\Commerce\SupplierCreditNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SupplierCreditNoteResource extends Resource
{
    protected static ?string $model = SupplierCreditNote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Receipt;

    protected static ?string $navigationLabel = 'Avoirs Fournisseurs';

    protected static string|\UnitEnum|null $navigationGroup = 'Achats';

    protected static ?string $modelLabel = 'Avoir Fournisseur';

    protected static ?string $pluralModelLabel = 'Avoirs Fournisseurs';

    public static function form(Schema $schema): Schema
    {
        return SupplierCreditNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SupplierCreditNotesTable::configure($table);
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
            'index' => ListSupplierCreditNotes::route('/'),
            'create' => CreateSupplierCreditNote::route('/create'),
            'edit' => EditSupplierCreditNote::route('/{record}/edit'),
        ];
    }
}
