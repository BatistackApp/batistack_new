<?php

namespace App\Filament\Laser\Resources\LaserOrders\Schemas;

use App\Enums\Laser\OrderStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LaserOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de commande')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de commande')
                            ->readOnly()
                            ->required(),

                        TextInput::make('client_id')
                            ->label('Client')
                            ->readOnly()
                            ->required(),

                        TextInput::make('laser_quote_id')
                            ->label('Devis d\'origine')
                            ->readOnly(),

                        Select::make('status')
                            ->label('Statut')
                            ->options(OrderStatus::class)
                            ->required()
                            ->native(false),
                    ]),

                Section::make('Conditions')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('terms')
                            ->label('Conditions particulières')
                            ->rows(3),
                    ]),
            ]);
    }
}
