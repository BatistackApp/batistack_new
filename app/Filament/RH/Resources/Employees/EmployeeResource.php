<?php

namespace App\Filament\RH\Resources\Employees;

use App\Filament\RH\Resources\Employees\Pages\CreateEmployee;
use App\Filament\RH\Resources\Employees\Pages\EditEmployee;
use App\Filament\RH\Resources\Employees\Pages\ListEmployees;
use App\Filament\RH\Resources\Employees\Pages\ViewEmployee;
use App\Filament\RH\Resources\Employees\RelationManagers\AbsencesRelationManager;
use App\Filament\RH\Resources\Employees\RelationManagers\ContractsRelationManager;
use App\Filament\RH\Resources\Employees\RelationManagers\EquipementsRelationManager;
use App\Filament\RH\Resources\Employees\RelationManagers\MedicalVisitsRelationManager;
use App\Filament\RH\Resources\Employees\RelationManagers\QualificationsRelationManager;
use App\Filament\RH\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\RH\Resources\Employees\Schemas\EmployeeInfolist;
use App\Filament\RH\Resources\Employees\Tables\EmployeesTable;
use App\Models\RH\Employee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::UserList;
    protected static ?string $modelLabel = 'Employé';
    protected static ?string $pluralModelLabel = 'Collaborateurs';

    protected static ?string $recordTitleAttribute = 'last_name';

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ContractsRelationManager::class,
            QualificationsRelationManager::class,
            AbsencesRelationManager::class,
            EquipementsRelationManager::class,
            MedicalVisitsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'create' => CreateEmployee::route('/create'),
            'view' => ViewEmployee::route('/{record}'),
            'edit' => EditEmployee::route('/{record}/edit'),
        ];
    }
}
