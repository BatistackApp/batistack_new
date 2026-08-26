<?php

namespace App\Filament\Resources\Units\Tables;

use App\Enums\Core\UnitType;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->emptyStateHeading('Aucune unité définie')
            ->columns([
                TextColumn::make('name')->label('Nom')
                    ->label('Nom')
                    ->searchable(),
                TextColumn::make('symbol')
                    ->label('Symbole')
                    ->badge(),
                TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (UnitType $state): string => match ($state) {
                        UnitType::TIME => 'warning',
                        UnitType::SURFACE, UnitType::VOLUME => 'info',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')
                    ->label('Active'),
            ])
            ->filters([
                SelectFilter::make('type')->label('Type')
                    ->options(UnitType::class)
                    ->label('Filtrer par grandeur'),
                TernaryFilter::make('is_active')
                    ->label('Afficher uniquement les actives'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
