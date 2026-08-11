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
                TextInput::make('name')->label('Nom')
                    ->label('Désignation')
                    ->required(),
                TextInput::make('account_code')->label('Code Comptable'),
                TextInput::make('default_depreciation_years')
                    ->label('Durée d\'amortissement par défaut (années)')
                    ->required()
                    ->numeric()
                    ->default(5),
                Select::make('default_method')
                    ->label('Méthode par défaut')
                    ->options(DepreciationMethod::class)
                    ->default('linear')
                    ->required(),
            ]);
    }
}
