<?php

namespace App\Filament\Commerce\Resources\CustomerCreditNotes\Schemas;

use App\Enums\Commerce\InvoiceStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerCreditNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('client_id')->label('Client')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->required()
                    ->searchable(),
                Select::make('customer_invoice_id')
                    ->label('Facture Client liée')
                    ->relationship('invoice', 'reference')
                    ->searchable(),
                TextInput::make('reference')->label('Référence')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(InvoiceStatus::class)
                    ->required()
                    ->default(InvoiceStatus::DRAFT),
                TextInput::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->prefix('€'),
                TextInput::make('total_ttc')
                    ->label('Total TTC')
                    ->numeric()
                    ->prefix('€'),
                Select::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('user', 'name')
                    ->searchable(),
            ]);
    }
}
