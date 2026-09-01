<?php

namespace App\Filament\Gpao\Gpao\MachineMaintenanceTickets;

use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Pages\ListMachineMaintenanceTickets;
use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Pages\ViewMachineMaintenanceTicket;
use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Schemas\MachineMaintenanceTicketInfolist;
use App\Filament\Gpao\Gpao\MachineMaintenanceTickets\Tables\MachineMaintenanceTicketsTable;
use App\Models\Gpao\MachineMaintenanceTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MachineMaintenanceTicketResource extends Resource
{
    protected static ?string $model = MachineMaintenanceTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Tickets de Maintenance';

    protected static string|null|\UnitEnum $navigationGroup = 'GPAO';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?string $modelLabel = 'Ticket de Maintenance';

    protected static ?string $pluralModelLabel = 'Tickets de Maintenance';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return MachineMaintenanceTicketsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MachineMaintenanceTicketInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMachineMaintenanceTickets::route('/'),
            'view' => ViewMachineMaintenanceTicket::route('/{record}'),
        ];
    }
}
