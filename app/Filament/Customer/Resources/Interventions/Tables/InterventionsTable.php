<?php

namespace App\Filament\Customer\Resources\Interventions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InterventionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable(),
                TextColumn::make('clientEquipment.name')->label('Équipement'),
                TextColumn::make('status')->label('Statut')->badge(),
                TextColumn::make('created_at')->label('Créée le')->date(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
