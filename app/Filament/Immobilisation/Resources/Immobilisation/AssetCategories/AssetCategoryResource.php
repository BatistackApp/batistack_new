<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories;

use App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Pages\CreateAssetCategory;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Pages\EditAssetCategory;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Pages\ListAssetCategories;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Schemas\AssetCategoryForm;
use App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Tables\AssetCategoriesTable;
use App\Models\Immobilisation\AssetCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetCategoryResource extends Resource
{
    protected static ?string $model = AssetCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return Schemas\AssetCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return Tables\AssetCategoriesTable::configure($table);
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
            'index' => ListAssetCategories::route('/'),
            'create' => CreateAssetCategory::route('/create'),
            'edit' => EditAssetCategory::route('/{record}/edit'),
        ];
    }
}
