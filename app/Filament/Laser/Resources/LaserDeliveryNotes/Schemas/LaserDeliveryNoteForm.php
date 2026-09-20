<?php

namespace App\Filament\Laser\Resources\LaserDeliveryNotes\Schemas;

use App\Enums\Laser\DeliveryStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LaserDeliveryNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations du bon de livraison')
                    ->visibleOn(['create', 'edit'])
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de BL')
                            ->readOnly()
                            ->required(),

                        TextInput::make('client_id')
                            ->label('Client')
                            ->formatStateUsing(fn ($state, $record): ?string => $record?->client?->name)
                            ->dehydrated(false)
                            ->readOnly(),

                        TextInput::make('laser_order_id')
                            ->label('Commande d\'origine')
                            ->formatStateUsing(fn ($state, $record): ?string => $record?->order?->reference)
                            ->dehydrated(false)
                            ->readOnly(),

                        Select::make('status')
                            ->label('Statut')
                            ->options(DeliveryStatus::class)
                            ->required()
                            ->native(false),

                        DatePicker::make('delivery_date')
                            ->label('Date de livraison')
                            ->native(false),
                    ]),
            ]);
    }
}
