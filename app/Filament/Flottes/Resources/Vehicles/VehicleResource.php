<?php

namespace App\Filament\Flottes\Resources\Vehicles;

use App\Filament\Flottes\Resources\Vehicles\Pages\CreateVehicle;
use App\Filament\Flottes\Resources\Vehicles\Pages\EditVehicle;
use App\Filament\Flottes\Resources\Vehicles\Pages\ListVehicles;
use App\Filament\Flottes\Resources\Vehicles\Pages\ViewVehicle;
use App\Filament\Flottes\Resources\Vehicles\RelationManagers\AssignmentsRelationManager;
use App\Filament\Flottes\Resources\Vehicles\RelationManagers\ContractsRelationManager;
use App\Filament\Flottes\Resources\Vehicles\RelationManagers\FinesRelationManager;
use App\Filament\Flottes\Resources\Vehicles\RelationManagers\InventoriesRelationManager;
use App\Filament\Flottes\Resources\Vehicles\RelationManagers\MaintenancesRelationManager;
use App\Filament\Flottes\Resources\Vehicles\Schemas\VehicleForm;
use App\Filament\Flottes\Resources\Vehicles\Schemas\VehicleInfolist;
use App\Filament\Flottes\Resources\Vehicles\Tables\VehiclesTable;
use App\Models\Flottes\Vehicle;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::Truck;

    protected static ?string $modelLabel = 'Véhicule';

    protected static ?string $pluralModelLabel = 'Véhicules';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return VehicleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return VehicleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehiclesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
            MaintenancesRelationManager::class,
            InventoriesRelationManager::class,
            ContractsRelationManager::class,
            FinesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicles::route('/'),
            'create' => CreateVehicle::route('/create'),
            'view' => ViewVehicle::route('/{record}'),
            'edit' => EditVehicle::route('/{record}/edit'),
        ];
    }
}
