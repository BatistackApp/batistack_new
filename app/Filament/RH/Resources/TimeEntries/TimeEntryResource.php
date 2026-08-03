<?php

namespace App\Filament\RH\Resources\TimeEntries;

use App\Filament\RH\Resources\TimeEntries\Pages\CreateTimeEntry;
use App\Filament\RH\Resources\TimeEntries\Pages\EditTimeEntry;
use App\Filament\RH\Resources\TimeEntries\Pages\ListTimeEntries;
use App\Filament\RH\Resources\TimeEntries\Schemas\TimeEntryForm;
use App\Filament\RH\Resources\TimeEntries\Tables\TimeEntriesTable;
use App\Models\RH\TimeEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TimeEntryResource extends Resource
{
    protected static ?string $model = TimeEntry::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Timer;
    
    protected static \UnitEnum|string|null $navigationGroup = 'Suivi & Pointages';

    protected static ?string $modelLabel = 'Pointage';

    protected static ?string $pluralModelLabel = 'Pointages';

    public static function form(Schema $schema): Schema
    {
        return TimeEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TimeEntriesTable::configure($table);
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
            'index' => ListTimeEntries::route('/'),
            'create' => CreateTimeEntry::route('/create'),
            'edit' => EditTimeEntry::route('/{record}/edit'),
        ];
    }
}
