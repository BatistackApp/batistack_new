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
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de BL')
                            ->readOnly()
                            ->required(),

                        TextInput::make('client.name')
                            ->label('Client')
                            ->readOnly(),

                        TextInput::make('order.reference')
                            ->label('Commande d\'origine')
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
