<?php

namespace App\Filament\Customer\Resources\CustomerQuotes\Tables;

use App\Enums\Commerce\QuoteStatus;
use App\Models\Commerce\CustomerQuote;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerQuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->query(
                CustomerQuote::where('client_id', auth()->user()->customer->third_party_id)
                    ->where('status', '!=', QuoteStatus::DRAFT)
                    ->newQuery()
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Numéro')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),

                TextColumn::make('total_ttc')
                    ->label('Montant TTC')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expiration')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
