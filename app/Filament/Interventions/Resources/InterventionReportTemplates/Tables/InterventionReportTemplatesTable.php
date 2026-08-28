<?php

namespace App\Filament\Interventions\Resources\InterventionReportTemplates\Tables;

use App\Enums\Interventions\InterventionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InterventionReportTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom du modèle')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('intervention_type')
                    ->label('Type d\'intervention')
                    ->badge()
                    ->sortable(),
                TextColumn::make('schema')
                    ->label('Questions')
                    ->formatStateUsing(fn ($state) => is_array($state) ? count($state).' question(s)' : '0 question'),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('intervention_type')
                    ->label('Type d\'intervention')
                    ->options(InterventionType::class),
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
