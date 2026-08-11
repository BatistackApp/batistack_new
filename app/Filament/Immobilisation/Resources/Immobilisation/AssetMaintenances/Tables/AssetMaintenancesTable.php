<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\AssetMaintenances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetMaintenancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('fixedAsset.name')
                    ->label('Machine')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('maintenance_date')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('type')->label('Type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'preventive' => 'Préventif',
                        'curative' => 'Curatif',
                        'control' => 'Contrôle VGP',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'preventive' => 'info',
                        'curative' => 'danger',
                        'control' => 'warning',
                        default => 'gray',
                    }),
                \Filament\Tables\Columns\TextColumn::make('cost_ht')
                    ->label('Coût HT')
                    ->money('EUR')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('provider_name')
                    ->label('Prestataire')
                    ->searchable(),
                \Filament\Tables\Columns\TextColumn::make('chantier.name')
                    ->label('Chantier Imputé')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')->label('Type')
                    ->options([
                        'preventive' => 'Entretien Préventif',
                        'curative' => 'Réparation Curative',
                        'control' => 'Contrôle Réglementaire (VGP)',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('fixed_asset_id')
                    ->relationship('fixedAsset', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Machine'),
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
