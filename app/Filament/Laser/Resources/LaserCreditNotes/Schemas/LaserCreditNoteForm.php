<?php

namespace App\Filament\Laser\Resources\LaserCreditNotes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LaserCreditNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de l\'avoir')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro d\'avoir')
                            ->readOnly()
                            ->required(),

                        TextInput::make('client.name')
                            ->label('Client')
                            ->readOnly(),

                        TextInput::make('invoice.reference')
                            ->label('Facture d\'origine')
                            ->readOnly(),

                        TextInput::make('status')
                            ->label('Statut')
                            ->readOnly(),

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

                        Textarea::make('reason')
                            ->label('Motif')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
