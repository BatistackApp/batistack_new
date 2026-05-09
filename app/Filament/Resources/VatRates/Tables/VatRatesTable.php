<?php

namespace App\Filament\Resources\VatRates\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VatRatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom du taux')
                    ->weight('bold'),

                TextColumn::make('rate')
                    ->label('Valeur (%)')
                    ->numeric(decimalPlaces: 2)
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_default')
                    ->label('Défaut')
                    ->boolean()
                    ->trueIcon(Phosphor::StarFill)
                    ->falseIcon(Phosphor::Star)
                    ->trueColor('warning'),

                ToggleColumn::make('is_active')
                    ->label('Actif'),
            ])
            ->reorderable('rate')
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
