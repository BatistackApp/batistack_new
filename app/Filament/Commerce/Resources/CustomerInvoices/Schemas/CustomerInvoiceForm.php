<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Schemas;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de facture')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de facture')
                            ->disabled()
                            ->required(),

                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('order_id')
                            ->label('Commande')
                            ->relationship('order', 'reference')
                            ->searchable()
                            ->preload(),

                        Select::make('type')
                            ->label('Type de facture')
                            ->options(InvoiceType::class)
                            ->required()
                            ->native(false),

                        Select::make('status')
                            ->label('Statut')
                            ->options(InvoiceStatus::class)
                            ->required()
                            ->native(false)
                            ->disabled(),

                        DatePicker::make('due_date')
                            ->label('Échéance')
                            ->required(),
                    ]),

                Section::make('Lignes de facture')
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

                                TextInput::make('unit_price')
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

                Section::make('Retenues et ajustements')
                    ->columns(2)
                    ->schema([
                        TextInput::make('retenue_amount')
                            ->label('Retenue de garantie')
                            ->numeric()
                            ->prefix('€')
                            ->readonly(),

                        TextInput::make('prorata_amount')
                            ->label('Compte prorata')
                            ->numeric()
                            ->prefix('€')
                            ->readonly(),

                        Textarea::make('notes')
                            ->label('Remarques')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
