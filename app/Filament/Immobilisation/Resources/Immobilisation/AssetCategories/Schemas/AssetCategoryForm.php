<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Schemas;

use App\Enums\Immobilisation\DepreciationMethod;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AssetCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('account_code'),
                TextInput::make('default_depreciation_years')
                    ->required()
                    ->numeric()
                    ->default(5),
                Select::make('default_method')
                    ->options(DepreciationMethod::class)
                    ->default('linear')
                    ->required(),
            ]);
    }
}
