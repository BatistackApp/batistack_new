<?php

namespace App\Filament\Customer\Resources\CustomerCreditNotes\Tables;

use App\Models\Commerce\CustomerCreditNote;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerCreditNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->query(
                CustomerCreditNote::where('client_id', auth()->user()->contact->third_party_id)
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('invoice.reference')
                    ->label('Facture liée')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->paginated([10, 25, 50]);
    }
}
