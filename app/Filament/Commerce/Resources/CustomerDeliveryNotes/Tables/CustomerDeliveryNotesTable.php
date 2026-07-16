<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class CustomerDeliveryNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('reference')->label('Référence')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('client.name')->label('Client')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('chantier.reference')->label('Chantier')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('delivery_date')->label('Date de livraison')->date('d/m/Y')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')->label('Statut')->badge()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
