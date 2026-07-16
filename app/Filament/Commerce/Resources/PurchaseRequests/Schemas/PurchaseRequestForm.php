<?php

namespace App\Filament\Commerce\Resources\PurchaseRequests\Schemas;

use Filament\Schemas\Schema;

class PurchaseRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('supplier_id')
                    ->label('Fournisseur')
                    ->relationship('supplier', 'name')
                    ->searchable(),
                \Filament\Forms\Components\Select::make('chantier_id')
                    ->label('Chantier')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('reference')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(\App\Enums\Commerce\QuoteStatus::class)
                    ->required()
                    ->default(\App\Enums\Commerce\QuoteStatus::DRAFT),
            ]);
    }
}
