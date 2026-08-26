<?php

namespace App\Filament\Commerce\Resources\PurchaseOrders\Schemas;

use App\Enums\Commerce\OrderStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseOrderForm
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
                Select::make('chantier_id')->label('Chantier')
                    ->label('Chantier')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                Select::make('purchase_request_id')
                    ->label('Demande d\'achat')
                    ->relationship('request', 'reference')
                    ->searchable(),
                TextInput::make('reference')->label('Référence')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(OrderStatus::class)
                    ->required()
                    ->default(OrderStatus::DRAFT),
                TextInput::make('total_ht')
                    ->label('Total HT')
                    ->numeric()
                    ->prefix('€'),
                TextInput::make('total_ttc')
                    ->label('Total TTC')
                    ->numeric()
                    ->prefix('€'),
                DatePicker::make('ordered_at')
                    ->label('Date de commande'),
                DatePicker::make('expected_delivery_date')
                    ->label('Date de livraison prévue'),
            ]);
    }
}
