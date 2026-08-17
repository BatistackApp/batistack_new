<?php

namespace App\Filament\Locations\Resources\RentalContracts;

use App\Filament\Locations\Resources\RentalContracts\Pages\CreateRentalContract;
use App\Filament\Locations\Resources\RentalContracts\Pages\EditRentalContract;
use App\Filament\Locations\Resources\RentalContracts\Pages\ListRentalContracts;
use App\Filament\Locations\Resources\RentalContracts\Pages\ViewRentalContract;
use App\Filament\Locations\Resources\RentalContracts\RelationManagers\EtatDesLieuxRelationManager;
use App\Filament\Locations\Resources\RentalContracts\Schemas\RentalContractForm;
use App\Filament\Locations\Resources\RentalContracts\Schemas\RentalContractInfolist;
use App\Filament\Locations\Resources\RentalContracts\Tables\RentalContractsTable;
use App\Models\Locations\RentalContract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RentalContractResource extends Resource
{
    protected static ?string $model = RentalContract::class;

    protected static ?string $modelLabel = 'Contrat de location';

    protected static ?string $pluralModelLabel = 'Contrats de location';

    protected static ?string $navigationLabel = 'Locations';

    protected static string|\UnitEnum|null $navigationGroup = 'Locations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return RentalContractForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RentalContractsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RentalContractInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            EtatDesLieuxRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRentalContracts::route('/'),
            'create' => CreateRentalContract::route('/create'),
            'view' => ViewRentalContract::route('/{record}'),
            'edit' => EditRentalContract::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
