<?php

namespace App\Filament\Commerce\Resources\CustomerOrders\Schemas;

use App\Enums\Commerce\OrderStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de commande')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de commande')
                            ->disabled()
                            ->required(),

                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('chantier_id')
                            ->label('Chantier')
                            ->relationship('chantier', 'reference')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('quote_id')
                            ->label('Devis d\'origine')
                            ->relationship('quote', 'reference')
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->label('Statut')
                            ->options(OrderStatus::class)
                            ->required()
                            ->native(false),

                        DatePicker::make('ordered_at')
                            ->label('Date de commande')
                            ->required(),
                    ]),

                Section::make('Lignes de commande')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->columns(5)
                            ->schema([
                                Select::make('item_id')
                                    ->label('Article')
                                    ->relationship('item', 'name')
                                    ->searchable()
                                    ->preload(),

                                TextInput::make('name')
                                    ->label('Description')
                                    ->required(),

                                TextInput::make('quantity')
                                    ->label('Quantité')
                                    ->numeric()
                                    ->required(),

                                TextInput::make('selling_price')
                                    ->label('Prix unitaire HT')
                                    ->numeric()
                                    ->required()
                                    ->prefix('€'),

                                TextInput::make('subtotal_ht')
                                    ->label('Sous-total HT')
                                    ->disabled()
                                    ->prefix('€'),
                            ]),
                    ]),

                Section::make('Totaux')
                    ->columns(3)
                    ->schema([
                        TextInput::make('total_ht')
                            ->label('Total HT')
                            ->disabled()
                            ->prefix('€'),

                        TextInput::make('total_tax')
                            ->label('Total TVA')
                            ->disabled()
                            ->prefix('€'),

                        TextInput::make('total_ttc')
                            ->label('Total TTC')
                            ->disabled()
                            ->prefix('€'),
                    ]),

                Section::make('Conditions')
                    ->schema([
                        Textarea::make('terms')
                            ->label('Conditions particulières')
                            ->rows(3),

                        TextInput::make('delivery_address')
                            ->label('Adresse de livraison'),
                    ]),
            ]);
    }
}
