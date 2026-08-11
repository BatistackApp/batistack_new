<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ImpairmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'impairments';
    protected static ?string $title = 'Dépréciations Exceptionnelles';
    protected static ?string $modelLabel = 'Dépréciation';
    protected static ?string $pluralModelLabel = 'Dépréciations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reason')
            ->columns([
                TextColumn::make('date')->label('Date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('Motif')
                    ->searchable(),
                TextColumn::make('amount')->label('Montant')
                    ->label('Montant de la perte')
                    ->money('EUR')
                    ->sortable()
                    ->color('danger'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Dépréciation se fait via l'action de la page View
            ])
            ->recordActions([
                // Lecture seule
            ])
            ->toolbarActions([
                //
            ]);
    }
}
