<?php

namespace App\Filament\Customer\Resources\CustomerInvoices\Tables;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Models\Commerce\CustomerInvoice;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->query(
                CustomerInvoice::where('client_id', auth()->user()->contact->third_party_id)
                    ->whereIn('status', collect(InvoiceStatus::cases())
                        ->reject(fn (InvoiceStatus $s) => in_array($s, [InvoiceStatus::DRAFT, InvoiceStatus::CANCELED]))
                        ->map(fn (InvoiceStatus $s) => $s->value)
                        ->all()
                    )
                    ->newQuery()
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceType $state) => $state->getLabel()),

                TextColumn::make('total_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state, CustomerInvoice $record): ?string => $record->is_overdue ? 'danger' : null),

                TextColumn::make('total_allocated')
                    ->label('Montant payé')
                    ->getStateUsing(fn (CustomerInvoice $record) => $record->total_allocated)
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('amount_remaining')
                    ->label('Solde dû')
                    ->getStateUsing(fn (CustomerInvoice $record) => $record->amount_remaining)
                    ->money('EUR')
                    ->sortable()
                    ->color(fn (float $state): ?string => $state > 0 ? 'warning' : 'success'),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => $state->getColor()),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(InvoiceStatus::class)
                    ->multiple(),
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(InvoiceType::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->paginated([10, 25, 50]);
    }
}
