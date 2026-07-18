<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Pages\CreateAssetMaintenance;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Pages\EditAssetMaintenance;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Pages\ListAssetMaintenances;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Schemas\AssetMaintenanceForm;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Tables\AssetMaintenancesTable;
use App\Models\Immobilisation\AssetMaintenance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetMaintenanceResource extends Resource
{
    protected static ?string $model = AssetMaintenance::class;

    protected static ?string $modelLabel = 'Réparation & Entretien';

    protected static ?string $pluralModelLabel = 'Historique Réparations';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion des Actifs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function form(Schema $schema): Schema
    {
        return AssetMaintenanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetMaintenancesTable::configure($table);
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
            'index' => ListAssetMaintenances::route('/'),
            'create' => CreateAssetMaintenance::route('/create'),
            'edit' => EditAssetMaintenance::route('/{record}/edit'),
        ];
    }
}
