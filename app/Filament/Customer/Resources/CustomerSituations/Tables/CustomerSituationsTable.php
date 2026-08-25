<?php

namespace App\Filament\Customer\Resources\CustomerSituations\Tables;

use App\Enums\Commerce\InvoiceStatus;
use App\Models\Commerce\CustomerSituation;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerSituationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('number', 'desc')
            ->query(
                CustomerSituation::whereHas('order', function ($query) {
                    $query->where('client_id', auth()->user()->contact->third_party_id);
                })
            )
            ->columns([
                TextColumn::make('number')
                    ->label('N°')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('order.reference')
                    ->label('Commande')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('periode_start')
                    ->label('Période')
                    ->getStateUsing(fn (CustomerSituation $record) => $record->periode_start?->format('d/m/Y').' — '.$record->periode_end?->format('d/m/Y'))
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('retenue_garantie_amount')
                    ->label('Retenue garantie')
                    ->money('EUR')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state): string => $state->getColor()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(InvoiceStatus::class)
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->paginated([10, 25, 50]);
    }
}
