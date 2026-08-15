<?php

namespace App\Filament\Interventions;

use App\Filament\Interventions\Pages\CreateMaintenanceContract;
use App\Filament\Interventions\Pages\EditMaintenanceContract;
use App\Filament\Interventions\Pages\ListMaintenanceContracts;
use App\Filament\Interventions\Pages\ViewMaintenanceContract;
use App\Filament\Interventions\RelationManagers\InterventionsRelationManager;
use App\Filament\Interventions\Schemas\MaintenanceContractForm;
use App\Filament\Interventions\Schemas\MaintenanceContractInfolist;
use App\Filament\Interventions\Tables\MaintenanceContractsTable;
use App\Models\Interventions\MaintenanceContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MaintenanceContractResource extends Resource
{
    protected static ?string $model = MaintenanceContract::class;

    protected static ?string $modelLabel = 'Contrat d\'entretien';

    protected static ?string $pluralModelLabel = 'Contrats d\'entretien';

    protected static string|null|\UnitEnum $navigationGroup = 'Maintenance préventive';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPath;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MaintenanceContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MaintenanceContractsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MaintenanceContractInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            InterventionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMaintenanceContracts::route('/'),
            'create' => CreateMaintenanceContract::route('/create'),
            'view' => ViewMaintenanceContract::route('/{record}'),
            'edit' => EditMaintenanceContract::route('/{record}/edit'),
        ];
    }
}
