<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerDeliveryNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable(),
                TextColumn::make('client.name')->label('Client')->searchable(),
                TextColumn::make('chantier.reference')->label('Chantier')->searchable(),
                TextColumn::make('delivery_date')->label('Date de livraison')->date('d/m/Y')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->check(fn ($records) => $records->every(fn ($r) => $r->canBeDeleted())),
                ]),
            ]);
    }
}
