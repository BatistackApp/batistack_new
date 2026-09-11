<?php

namespace App\Filament\Laser\Resources\LaserInvoices\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';

    protected static ?string $title = 'Lignes de la facture';

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
                    ->label('Qté totale'),

                TextColumn::make('quantity_invoiced')
                    ->label('Qté facturée'),

                TextColumn::make('unit_price_ht')
                    ->label('P.U. HT')
                    ->money('EUR'),

                TextColumn::make('discount_pct')
                    ->label('Remise')
                    ->suffix('%'),

                TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR'),

                TextColumn::make('weight_kg')
                    ->label('Poids')
                    ->suffix(' kg'),
            ]);
    }
}
