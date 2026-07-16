<?php

namespace App\Filament\Commerce\Resources\SubcontractorSituations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class SubcontractorSituationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('subcontractor.name')
                    ->label('Sous-traitant')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('progress_percentage')
                    ->label('Avancement (%)')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
