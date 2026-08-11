<?php

namespace App\Filament\Commerce\Resources\SupplierCreditNotes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class SupplierCreditNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('reference')->label('Référence')
                    ->label('Référence')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Fournisseur')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('invoice.reference')
                    ->label('Facture')
                    ->searchable()
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('total_ht')
                    ->label('Total HT')
                    ->money('EUR')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')->label('Statut')
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
