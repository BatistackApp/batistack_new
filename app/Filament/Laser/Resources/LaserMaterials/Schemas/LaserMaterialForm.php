<?php

namespace App\Filament\Laser\Resources\LaserMaterials\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LaserMaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom du matériau')
                            ->required()
                            ->maxLength(255),

                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),

                        TextInput::make('density_kg_m3')
                            ->label('Densité (kg/m³)')
                            ->numeric()
                            ->required()
                            ->minValue(0),

                        TextInput::make('price_per_kg')
                            ->label('Prix au kg (€)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('€'),

                        TextInput::make('price_per_meter')
                            ->label('Prix au mètre de découpe (€)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('€'),

                        TextInput::make('min_thickness_mm')
                            ->label('Épaisseur min (mm)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->suffix('mm'),

                        TextInput::make('max_thickness_mm')
                            ->label('Épaisseur max (mm)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->suffix('mm'),
                    ]),
            ]);
    }
}
