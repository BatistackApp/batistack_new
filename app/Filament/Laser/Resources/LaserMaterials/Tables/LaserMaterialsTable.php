<?php

namespace App\Filament\Laser\Resources\LaserMaterials\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class LaserMaterialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name', 'asc')
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('density_kg_m3')
                    ->label('Densité')
                    ->suffix(' kg/m³')
                    ->sortable(),

                TextColumn::make('price_per_kg')
                    ->label('Prix/kg')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('price_per_meter')
                    ->label('Prix/m')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('min_thickness_mm')
                    ->label('Ép. min')
                    ->suffix(' mm')
                    ->sortable(),

                TextColumn::make('max_thickness_mm')
                    ->label('Ép. max')
                    ->suffix(' mm')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Actif')
                    ->default(true),
            ]);
    }
}
