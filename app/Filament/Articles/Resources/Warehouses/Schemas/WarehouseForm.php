<?php

namespace App\Filament\Articles\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Détails du Dépôt')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom du dépôt')
                            ->required(),
                        TextInput::make('location')
                            ->label('Localisation / Adresse'),
                        Toggle::make('is_active')
                            ->label('Dépôt opérationnel')
                            ->default(true)
                            ->onColor('success'),
                    ])->columns(2),
            ]);
    }
}
