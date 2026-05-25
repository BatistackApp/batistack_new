<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Tables;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Tiers\AddressType;
use App\Models\Commerce\CustomerDeliveryNote;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerDeliveryNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reference', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('#')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.legal_name')
                    ->label('Client'),

                TextColumn::make('client.addresses')
                    ->label('Livrée à')
                    ->formatStateUsing(function (CustomerDeliveryNote $record) {
                        return $record->client->addresses()->where('type', AddressType::DELIVERY)->first()->full_address ?? 'N/A';
                    }),

                TextColumn::make('status')
                    ->label('Etat')
                    ->badge()
                    ->formatStateUsing(fn (CustomerDeliveryNote $record) => $record->status === DeliveryStatus::DELIVERED ? 'Livrée le '.$record->delivery_date->format('d/m/Y') : $record->status->getLabel()),

                TextColumn::make('items_count')
                    ->label('Nb articles')
                    ->badge()
                    ->alignCenter()
                    ->counts('items'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(DeliveryStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
