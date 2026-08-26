<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    protected static ?string $title = 'Historique d\'Affectation';

    protected static ?string $modelLabel = 'Affectation';

    protected static ?string $pluralModelLabel = 'Affectations';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('assigned_at', 'desc')
            ->columns([
                TextColumn::make('chantier.name')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('assigned_at')
                    ->label('Affecté le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('released_at')
                    ->label('Libéré le')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('En cours')
                    ->sortable(),
                TextColumn::make('assigner.name')
                    ->label('Par')
                    ->placeholder('-'),
                TextColumn::make('reason')
                    ->label('Motif')
                    ->searchable()
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Lecture seule : la trace est générée automatiquement par l'observer
            ])
            ->recordActions([
                //
            ])
            ->toolbarActions([
                //
            ]);
    }
}
