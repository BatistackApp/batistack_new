<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Schemas;

use App\Enums\Tiers\ThirdPartyType;
use App\Models\Tiers\ThirdParty;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerDeliveryNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Select::make('client_id')
                            ->label('Client')
                            ->options(ThirdParty::where('type', ThirdPartyType::CLIENT)->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->preload(),

                        Select::make('chantier_id')
                            ->label('Chantier Affilier')
                            ->relationship(
                                name: 'chantier',
                                titleAttribute: 'reference',
                                modifyQueryUsing: function (Builder $query, Get $get) {
                                    $clientId = $get('client_id');
                                    if (! $clientId) {
                                        return $query->whereNull('id');
                                    }

                                    return $query->where('client_id', $clientId);
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->reference.' - '.$record->name)
                            ->disabled(fn (Get $get) => ! $get('client_id'))
                            ->searchable()
                            ->preload(),

                        Select::make('customer_order_id')
                            ->label('Commande Affilié')
                            ->relationship(
                                name: 'order',
                                titleAttribute: 'reference',
                                modifyQueryUsing: function (Builder $query, Get $get) {
                                    $clientId = $get('client_id');
                                    if (! $clientId) {
                                        return $query->whereNull('id');
                                    }

                                    return $query->where('client_id', $clientId);
                                }
                            )
                            ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->created_at->format('d-m-Y').' - '.$record->reference)
                            ->disabled(fn (Get $get) => !$get('client_id'))
                            ->searchable()
                            ->preload(),
                    ]),
            ]);
    }
}
