<?php

namespace App\Filament\Commerce\Resources\CustomerInvoices\Schemas;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use Ariefng\FilamentCalculator\Actions\CalculatorAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CustomerInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de facture')
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        Select::make('client_id')
                            ->label('Client')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),

                        Select::make('order_id')
                            ->label('Commande')
                            ->relationship('order', 'reference', function (Builder $query, Get $get) {
                                $clientId = $get('client_id');

                                if (! $clientId) {
                                    return $query->raw('1 = 0');
                                }

                                return $query->where('client_id', $clientId);
                            })
                            ->searchable()
                            ->preload(),

                        Select::make('type')
                            ->label('Type de facture')
                            ->options(InvoiceType::class)
                            ->required()
                            ->live()
                            ->native(false),


                        DatePicker::make('due_date')
                            ->label('Échéance')
                            ->required()
                            ->default(now()),

                        TextInput::make('amountAcompte')
                            ->label("Montant de l'acompte")
                            ->suffix('€')
                            ->visible(fn (Get $get) => $get('type') === InvoiceType::ACOMPTE),

                        Fieldset::make('Retenues et ajustements')
                            ->columns(2)
                            ->columnSpanFull()
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
                    ]),
            ]);
    }
}
