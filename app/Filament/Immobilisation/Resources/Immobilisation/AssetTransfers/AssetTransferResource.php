<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Pages\CreateAssetTransfer;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Pages\EditAssetTransfer;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Pages\ListAssetTransfers;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Pages\ViewAssetTransfer;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Schemas\AssetTransferForm;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Schemas\AssetTransferInfolist;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetTransfers\Tables\AssetTransfersTable;
use App\Models\Immobilisation\AssetTransfer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetTransferResource extends Resource
{
    protected static ?string $model = AssetTransfer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AssetTransferForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AssetTransferInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetTransfersTable::configure($table);
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
            'index' => ListAssetTransfers::route('/'),
            'create' => CreateAssetTransfer::route('/create'),
            'view' => ViewAssetTransfer::route('/{record}'),
            'edit' => EditAssetTransfer::route('/{record}/edit'),
        ];
    }
}
