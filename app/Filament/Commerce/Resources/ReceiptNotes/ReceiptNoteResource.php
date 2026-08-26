<?php

namespace App\Filament\Commerce\Resources\ReceiptNotes;

use App\Filament\Commerce\Resources\ReceiptNotes\Pages\CreateReceiptNote;
use App\Filament\Commerce\Resources\ReceiptNotes\Pages\EditReceiptNote;
use App\Filament\Commerce\Resources\ReceiptNotes\Pages\ListReceiptNotes;
use App\Filament\Commerce\Resources\ReceiptNotes\RelationManagers\ItemsRelationManager;
use App\Filament\Commerce\Resources\ReceiptNotes\Schemas\ReceiptNoteForm;
use App\Filament\Commerce\Resources\ReceiptNotes\Tables\ReceiptNotesTable;
use App\Models\Commerce\ReceiptNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ReceiptNoteResource extends Resource
{
    protected static ?string $model = ReceiptNote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Package;

    protected static ?string $navigationLabel = 'Bons de réception';

    protected static string|\UnitEnum|null $navigationGroup = 'Logistique';

    protected static ?string $modelLabel = 'Bon de réception';

    protected static ?string $pluralModelLabel = 'Bons de réception';

    public static function form(Schema $schema): Schema
    {
        return ReceiptNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceiptNotesTable::configure($table);
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
            'index' => ListReceiptNotes::route('/'),
            'create' => CreateReceiptNote::route('/create'),
            'edit' => EditReceiptNote::route('/{record}/edit'),
        ];
    }
}
