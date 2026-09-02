<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets;

use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Pages\CreateFixedAsset;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Pages\EditFixedAsset;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Pages\ListFixedAssets;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Pages\ViewFixedAsset;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers\AssignmentsRelationManager;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers\DisposalRelationManager;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers\ImpairmentsRelationManager;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers\MaintenancesRelationManager;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Schemas\FixedAssetForm;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Schemas\FixedAssetInfolist;
use App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Tables\FixedAssetsTable;
use App\Models\Immobilisation\FixedAsset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FixedAssetResource extends Resource
{
    protected static ?string $model = FixedAsset::class;

    protected static ?string $modelLabel = 'Immobilisation';

    protected static ?string $pluralModelLabel = 'Immobilisations';

    protected static string|null|\UnitEnum $navigationGroup = 'Gestion des Actifs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FixedAssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FixedAssetsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return FixedAssetInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
            MaintenancesRelationManager::class,
            ImpairmentsRelationManager::class,
            DisposalRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFixedAssets::route('/'),
            'create' => CreateFixedAsset::route('/create'),
            'view' => ViewFixedAsset::route('/{record}'),
            'edit' => EditFixedAsset::route('/{record}/edit'),
        ];
    }
}
