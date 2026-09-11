<?php

namespace App\Filament\Laser\Resources\LaserDeliveryNotes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Lignes du bon de livraison';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('material.name')
                    ->label('Matériau')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(30),

                TextColumn::make('length_mm')
                    ->label('Longueur')
                    ->suffix(' mm'),

                TextColumn::make('width_mm')
                    ->label('Largeur')
                    ->suffix(' mm'),

                TextColumn::make('thickness_mm')
                    ->label('Épaisseur')
                    ->suffix(' mm'),

                TextColumn::make('quantity')
                    ->label('Qté cmd'),

                TextColumn::make('quantity_delivered')
                    ->label('Qté livrée')
                    ->color(fn ($record) => $record->quantity_delivered < $record->quantity ? 'warning' : null),

                TextColumn::make('weight_kg')
                    ->label('Poids')
                    ->suffix(' kg'),
            ]);
    }
}
