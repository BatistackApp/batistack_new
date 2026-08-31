<?php

namespace App\Filament\Locations\Resources\OutboundRentalContracts;

use App\Filament\Locations\Resources\OutboundRentalContracts\Pages\CreateOutboundRentalContract;
use App\Filament\Locations\Resources\OutboundRentalContracts\Pages\EditOutboundRentalContract;
use App\Filament\Locations\Resources\OutboundRentalContracts\Pages\ListOutboundRentalContracts;
use App\Filament\Locations\Resources\OutboundRentalContracts\Pages\ViewOutboundRentalContract;
use App\Filament\Locations\Resources\OutboundRentalContracts\Schemas\OutboundRentalContractForm;
use App\Filament\Locations\Resources\OutboundRentalContracts\Schemas\OutboundRentalContractInfolist;
use App\Filament\Locations\Resources\OutboundRentalContracts\Tables\OutboundRentalContractsTable;
use App\Models\Locations\OutboundRentalContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OutboundRentalContractResource extends Resource
{
    protected static ?string $model = OutboundRentalContract::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Schema $schema): Schema
    {
        return OutboundRentalContractForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OutboundRentalContractInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OutboundRentalContractsTable::configure($table);
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
            'index' => ListOutboundRentalContracts::route('/'),
            'create' => CreateOutboundRentalContract::route('/create'),
            'view' => ViewOutboundRentalContract::route('/{record}'),
            'edit' => EditOutboundRentalContract::route('/{record}/edit'),
        ];
    }
}
