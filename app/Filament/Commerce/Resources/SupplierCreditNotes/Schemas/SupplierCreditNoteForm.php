<?php

namespace App\Filament\Commerce\Resources\SupplierCreditNotes\Schemas;

use Filament\Schemas\Schema;

class SupplierCreditNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('supplier_id')
                    ->label('Fournisseur')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\Select::make('supplier_invoice_id')
                    ->label('Facture Fournisseur liée')
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
