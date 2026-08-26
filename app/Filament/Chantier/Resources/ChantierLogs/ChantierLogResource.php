<?php

namespace App\Filament\Chantier\Resources\ChantierLogs;

use App\Filament\Chantier\Resources\ChantierLogs\Pages\CreateChantierLog;
use App\Filament\Chantier\Resources\ChantierLogs\Pages\EditChantierLog;
use App\Filament\Chantier\Resources\ChantierLogs\Pages\ListChantierLogs;
use App\Filament\Chantier\Resources\ChantierLogs\Schemas\ChantierLogForm;
use App\Filament\Chantier\Resources\ChantierLogs\Tables\ChantierLogsTable;
use App\Models\Chantiers\ChantierLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierLogResource extends Resource
{
    protected static ?string $model = ChantierLog::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Notebook;

    protected static ?string $modelLabel = 'Note de Journal';

    protected static ?string $pluralModelLabel = 'Journal de Chantier';

    protected static string|\UnitEnum|null $navigationGroup = 'Chantiers';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ChantierLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChantierLogsTable::configure($table);
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
            'index' => ListChantierLogs::route('/'),
            'create' => CreateChantierLog::route('/create'),
            'edit' => EditChantierLog::route('/{record}/edit'),
        ];
    }
}
