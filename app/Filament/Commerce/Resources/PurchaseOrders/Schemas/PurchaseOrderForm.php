<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders\Schemas;

use Filament\Schemas\Schema;

class PurchaseOrderForm
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
                \Filament\Forms\Components\Select::make('chantier_id')
                    ->label('Chantier')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\Select::make('purchase_request_id')
                    ->label('Demande d\'achat')
                    ->relationship('request', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('reference')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(\App\Enums\Commerce\OrderStatus::class)
                    ->required()
                    ->default(\App\Enums\Commerce\OrderStatus::DRAFT),
                \Filament\Forms\Components\TextInput::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->prefix('€'),
                \Filament\Forms\Components\TextInput::make('total_ttc')
                    ->label('Total TTC')
                    ->numeric()
                    ->prefix('€'),
                \Filament\Forms\Components\DatePicker::make('ordered_at')
                    ->label('Date de commande'),
                \Filament\Forms\Components\DatePicker::make('expected_delivery_date')
                    ->label('Date de livraison prévue'),
            ]);
    }
}
