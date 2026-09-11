<?php

namespace App\Filament\Laser\Resources\LaserCreditNotes;

use App\Filament\Laser\Resources\LaserCreditNotes\Pages\ListLaserCreditNotes;
use App\Filament\Laser\Resources\LaserCreditNotes\Pages\ViewLaserCreditNote;
use App\Filament\Laser\Resources\LaserCreditNotes\Schemas\LaserCreditNoteForm;
use App\Filament\Laser\Resources\LaserCreditNotes\Tables\LaserCreditNotesTable;
use App\Models\Laser\LaserCreditNote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;
use UnitEnum;

class LaserCreditNoteResource extends Resource
{
    protected static ?string $model = LaserCreditNote::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::ReceiptX;

    protected static string|UnitEnum|null $navigationGroup = 'Commercial';

    protected static ?string $navigationLabel = 'Avoirs';

    protected static ?string $modelLabel = 'Avoir';

    protected static ?string $pluralModelLabel = 'Avoirs';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['client', 'invoice']);
    }

    public static function form(Schema $schema): Schema
    {
        return LaserCreditNoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LaserCreditNotesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLaserCreditNotes::route('/'),
            'view' => ViewLaserCreditNote::route('/{record}'),
        ];
    }
}
