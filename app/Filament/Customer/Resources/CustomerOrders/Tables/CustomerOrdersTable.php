<?php

namespace App\Filament\Customer\Resources\CustomerOrders\Tables;

use App\Enums\Commerce\OrderStatus;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reference', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', '!=', OrderStatus::DRAFT))
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
