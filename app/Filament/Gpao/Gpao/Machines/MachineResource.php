<?php

namespace App\Filament\Gpao\Gpao\Machines;

use App\Filament\Gpao\Gpao\Machines\Pages\CreateMachine;
use App\Filament\Gpao\Gpao\Machines\Pages\EditMachine;
use App\Filament\Gpao\Gpao\Machines\Pages\ListMachines;
use App\Filament\Gpao\Gpao\Machines\RelationManagers\MaintenanceTicketsRelationManager;
use App\Filament\Gpao\Gpao\Machines\Schemas\MachineForm;
use App\Filament\Gpao\Gpao\Machines\Tables\MachinesTable;
use App\Models\Gpao\Machine;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MachineResource extends Resource
{
    protected static ?string $model = Machine::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return MachineForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MachinesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MaintenanceTicketsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMachines::route('/'),
            'create' => CreateMachine::route('/create'),
            'edit' => EditMachine::route('/{record}/edit'),
        ];
    }
}
