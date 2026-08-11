<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nom')
                    ->label('Désignation')
                    ->searchable(),
                TextColumn::make('account_code')
                    ->label('Code Comptable')
                    ->searchable(),
                TextColumn::make('default_depreciation_years')
                    ->label('Durée d\'amortissement (ans)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('default_method')
                    ->label('Méthode')
                    ->badge()
                    ->searchable(),
                TextColumn::make('created_at')->label('Créé le')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->label('Mis à jour le')
                    ->label('Mis à jour le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('default_method')
                    ->label('Méthode d\'amortissement')
                    ->options(\App\Enums\Immobilisation\DepreciationMethod::class),
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
