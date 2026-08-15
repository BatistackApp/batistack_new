<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Pages\ListAssetMaintenanceTickets;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Pages\ViewAssetMaintenanceTicket;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Schemas\AssetMaintenanceTicketForm;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Schemas\AssetMaintenanceTicketInfolist;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenanceTickets\Tables\AssetMaintenanceTicketsTable;
use App\Models\Immobilisation\AssetMaintenanceTicket;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetMaintenanceTicketResource extends Resource
{
    protected static ?string $model = AssetMaintenanceTicket::class;

    protected static ?string $modelLabel = 'Déclaration de casse';

    protected static ?string $pluralModelLabel = 'Déclarations de casse';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion des Actifs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return AssetMaintenanceTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetMaintenanceTicketsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetMaintenanceTicketInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetMaintenanceTickets::route('/'),
            'view' => ViewAssetMaintenanceTicket::route('/{record}'),
        ];
    }
}
