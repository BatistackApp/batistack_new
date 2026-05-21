<?php

namespace App\Filament\Commerce\Resources\CustomerQuotes\Schemas;

use App\Enums\Commerce\QuoteStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerQuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations générales')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de devis')
                            ->disabled()
                            ->required(),

                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),

                        Select::make('chantier_id')
                            ->label('Chantier')
                            ->relationship('chantier', 'reference')
                            ->searchable()
                            ->preload(),

                        Select::make('status')
                            ->label('Statut')
                            ->options(QuoteStatus::class)
                            ->required()
                            ->native(false),

                        DatePicker::make('expires_at')
                            ->label('Date d\'expiration')
                            ->required(),

                        DatePicker::make('signed_at')
                            ->label('Date de signature'),
                    ]),

                Section::make('Lignes de devis')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->columns(6)
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

                                Select::make('vat_rate_id')
                                    ->label('TVA')
                                    ->relationship('vatRate', 'name')
                                    ->preload()
                                    ->required(),

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

                Section::make('Informations supplémentaires')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(3),

                        TextInput::make('terms')
                            ->label('Conditions de paiement'),
                    ]),
            ]);
    }
}
