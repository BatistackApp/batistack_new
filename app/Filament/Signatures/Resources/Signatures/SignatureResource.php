<?php

namespace App\Filament\Signatures\Resources\Signatures;

use App\Filament\Signatures\Resources\Signatures\Pages\ListSignatures;
use App\Filament\Signatures\Resources\Signatures\Pages\ViewSignature;
use App\Filament\Signatures\Resources\Signatures\RelationManagers\SignersRelationManager;
use App\Filament\Signatures\Resources\Signatures\Schemas\SignatureInfolist;
use App\Filament\Signatures\Resources\Signatures\Tables\SignaturesTable;
use App\Models\Core\Signature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class SignatureResource extends Resource
{
    protected static ?string $model = Signature::class;

    protected static string|BackedEnum|null $navigationIcon = Phosphor::PenNib;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestion des Signatures';

    protected static ?string $modelLabel = 'Signature';

    protected static ?string $pluralModelLabel = 'Signatures';

    protected static ?string $recordTitleAttribute = 'token';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withSignerCounts();
    }

    public static function infolist(Schema $schema): Schema
    {
        return SignatureInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SignaturesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SignersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSignatures::route('/'),
            'view' => ViewSignature::route('/{record}'),
        ];
    }
}
