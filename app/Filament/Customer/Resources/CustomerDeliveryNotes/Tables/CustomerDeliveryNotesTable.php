<?php

namespace App\Filament\Customer\Resources\CustomerDeliveryNotes\Tables;

use App\Enums\Commerce\DeliveryStatus;
use App\Enums\Tiers\AddressType;
use App\Models\Commerce\CustomerDeliveryNote;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerDeliveryNotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('reference', 'desc')
            ->query(
                CustomerDeliveryNote::where('client_id', auth()->user()->contact->third_party_id)
                    ->newQuery()
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Référence'),

                TextColumn::make('client.addresses')
                    ->label('Adresse de Livraison')
                    ->formatStateUsing(function (CustomerDeliveryNote $record) {
                        $address = $record->client->addresses()->where('type', AddressType::DELIVERY)->first();
                        if ($address) {
                            return "{$address->street}<br>{$address->zip_code} {$address->city}";
                        } else {
                            return 'N/A';
                        }
                    })
                    ->html(),

                TextColumn::make('items_count')
                    ->label('Nb articles')
                    ->badge()
                    ->alignCenter()
                    ->counts('items'),

                TextColumn::make('status')
                    ->label('Etat')
                    ->badge()
                    ->formatStateUsing(fn (CustomerDeliveryNote $record) => $record->status === DeliveryStatus::DELIVERED ? 'Livrée le '.$record->delivery_date->format('d/m/Y') : $record->status->getLabel()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
