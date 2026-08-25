<?php

namespace App\Filament\Customer\Resources\CustomerOrders\Tables;

use App\Enums\Commerce\OrderStatus;
use App\Models\Commerce\CustomerOrder;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reference', 'desc')
            ->query(
                CustomerOrder::where('client_id', auth()->user()->contact->third_party_id)
                    ->where('status', '!=', OrderStatus::DRAFT)
                    ->newQuery()
            )
            ->columns([
                TextColumn::make('reference')->label('Référence')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('chantier.reference')
                    ->label('Chantier')
                    ->formatStateUsing(fn (Model $record) => $record->chantier->name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')->label('Statut')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
