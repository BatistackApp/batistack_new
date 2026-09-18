<?php

namespace App\Filament\Laser\Resources\LaserCreditNotes\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LaserCreditNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice.reference')
                    ->label('Facture')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('total_ttc')
                    ->label('Total TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('reason')
                    ->label('Motif')
                    ->limit(30),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ]);
    }
}
