<?php

namespace App\Filament\RH\Resources\CibtpDeclarations;

use App\Filament\RH\Resources\CibtpDeclarations\Pages\CreateCibtpDeclaration;
use App\Filament\RH\Resources\CibtpDeclarations\Pages\EditCibtpDeclaration;
use App\Filament\RH\Resources\CibtpDeclarations\Pages\ListCibtpDeclarations;
use App\Filament\RH\Resources\CibtpDeclarations\Schemas\CibtpDeclarationForm;
use App\Filament\RH\Resources\CibtpDeclarations\Tables\CibtpDeclarationsTable;
use App\Models\CibtpDeclaration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CibtpDeclarationResource extends Resource
{
    protected static ?string $model = CibtpDeclaration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'date';

    public static function form(Schema $schema): Schema
    {
        return CibtpDeclarationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CibtpDeclarationsTable::configure($table);
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
            'index' => ListCibtpDeclarations::route('/'),
            'create' => CreateCibtpDeclaration::route('/create'),
            'edit' => EditCibtpDeclaration::route('/{record}/edit'),
        ];
    }
}
