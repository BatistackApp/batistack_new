<?php

namespace App\Filament\Laser\Resources\LaserInvoices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LaserInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de la facture')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro de facture')
                            ->readOnly()
                            ->required(),

                        TextInput::make('client.name')
                            ->label('Client')
                            ->readOnly(),

                        TextInput::make('order.reference')
                            ->label('Commande')
                            ->readOnly(),

                        TextInput::make('status')
                            ->label('Statut')
                            ->readOnly(),

                        DatePicker::make('due_date')
                            ->label('Date d\'échéance')
                            ->native(false),

                        TextInput::make('total_ht')
                            ->label('Total HT')
                            ->suffix('€')
                            ->readOnly(),

                        TextInput::make('total_tva')
                            ->label('TVA')
                            ->suffix('€')
                            ->readOnly(),

                        TextInput::make('total_ttc')
                            ->label('Total TTC')
                            ->suffix('€')
                            ->readOnly(),
                    ]),
            ]);
    }
}
