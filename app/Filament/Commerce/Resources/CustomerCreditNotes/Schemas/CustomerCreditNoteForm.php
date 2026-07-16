<?php

namespace App\Filament\Commerce\Resources\CustomerCreditNotes\Schemas;

use Filament\Schemas\Schema;

class CustomerCreditNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\Select::make('customer_invoice_id')
                    ->label('Facture Client liée')
                    ->relationship('invoice', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('reference')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(\App\Enums\Commerce\InvoiceStatus::class)
                    ->required()
                    ->default(\App\Enums\Commerce\InvoiceStatus::DRAFT),
                \Filament\Forms\Components\TextInput::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->prefix('€'),
                \Filament\Forms\Components\TextInput::make('total_ttc')
                    ->label('Total TTC')
                    ->numeric()
                    ->prefix('€'),
                \Filament\Forms\Components\Select::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('user', 'name')
                    ->searchable(),
            ]);
    }
}
