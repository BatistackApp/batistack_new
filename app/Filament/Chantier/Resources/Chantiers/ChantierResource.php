<?php

namespace App\Filament\Chantier\Resources\Chantiers;

use App\Filament\Chantier\Resources\Chantiers\Pages\CreateChantier;
use App\Filament\Chantier\Resources\Chantiers\Pages\EditChantier;
use App\Filament\Chantier\Resources\Chantiers\Pages\ListChantiers;
use App\Filament\Chantier\Resources\Chantiers\Pages\ViewChantier;
use App\Filament\Chantier\Resources\Chantiers\RelationManagers\LogsRelationManager;
use App\Filament\Chantier\Resources\Chantiers\RelationManagers\MembersRelationManager;
use App\Filament\Chantier\Resources\Chantiers\RelationManagers\PhasesRelationManager;
use App\Filament\Chantier\Resources\Chantiers\RelationManagers\SubcontractorsRelationManager;
use App\Filament\Chantier\Resources\Chantiers\Schemas\ChantierForm;
use App\Filament\Chantier\Resources\Chantiers\Schemas\ChantierInfolist;
use App\Filament\Chantier\Resources\Chantiers\Tables\ChantiersTable;
use App\Models\Chantiers\Chantier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierResource extends Resource
{
    protected static ?string $model = Chantier::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::HardHat;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ChantierForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ChantierInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ChantiersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PhasesRelationManager::class,
            MembersRelationManager::class,
            SubcontractorsRelationManager::class,
            LogsRelationManager::class,
            \App\Filament\Chantier\Resources\Chantiers\RelationManagers\InvoicesRelationManager::class,
            \App\Filament\Chantier\Resources\Chantiers\RelationManagers\WeatherAlertsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChantiers::route('/'),
            'create' => CreateChantier::route('/create'),
            'view' => ViewChantier::route('/{record}'),
            'edit' => EditChantier::route('/{record}/edit'),
        ];
    }
}
