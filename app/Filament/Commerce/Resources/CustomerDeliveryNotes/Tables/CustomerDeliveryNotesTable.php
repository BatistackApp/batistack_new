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
                \Filament\Tables\Columns\TextColumn::make('reference')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('client.name')->label('Client')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('chantier.reference')->label('Chantier')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('delivery_date')->date('d/m/Y')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Tables\Actions\ViewAction::make(),
                \Filament\Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
