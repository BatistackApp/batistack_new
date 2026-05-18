<?php

namespace App\Filament\Flottes\Resources\VehicleAssignments;

use App\Filament\Flottes\Resources\VehicleAssignments\Pages\CreateVehicleAssignment;
use App\Filament\Flottes\Resources\VehicleAssignments\Pages\EditVehicleAssignment;
use App\Filament\Flottes\Resources\VehicleAssignments\Pages\ListVehicleAssignments;
use App\Filament\Flottes\Resources\VehicleAssignments\Schemas\VehicleAssignmentForm;
use App\Filament\Flottes\Resources\VehicleAssignments\Tables\VehicleAssignmentsTable;
use App\Models\Flottes\VehicleAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VehicleAssignmentResource extends Resource
{
    protected static ?string $model = VehicleAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::CalendarCheck;
    protected static ?string $modelLabel = 'Affectation / Trajet';
    protected static ?string $pluralModelLabel = 'Planning des affectations';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return VehicleAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleAssignmentsTable::configure($table);
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
            'index' => ListVehicleAssignments::route('/'),
            'create' => CreateVehicleAssignment::route('/create'),
            'edit' => EditVehicleAssignment::route('/{record}/edit'),
        ];
    }
}
