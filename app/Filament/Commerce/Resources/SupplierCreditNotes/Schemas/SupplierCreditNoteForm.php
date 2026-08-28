<?php

namespace App\Filament\Commerce\Resources\SupplierCreditNotes\Schemas;

use App\Enums\Commerce\InvoiceStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierCreditNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label('Fournisseur')
                    ->relationship('supplier', 'name')
                    ->required()
                    ->searchable(),
                Select::make('supplier_invoice_id')
                    ->label('Facture Fournisseur liée')
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
