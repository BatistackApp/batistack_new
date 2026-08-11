<?php

namespace App\Filament\Commerce\Resources\ReceiptNotes\Schemas;

use Filament\Schemas\Schema;

class ReceiptNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('purchase_order_id')
                    ->label('Commande Fournisseur')
                    ->relationship('order', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\Select::make('warehouse_id')
                    ->label('Entrepôt')
                    ->relationship('warehouse', 'name')
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('reference')->label('Référence')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(\App\Enums\Commerce\DeliveryStatus::class)
                    ->required()
                    ->default(\App\Enums\Commerce\DeliveryStatus::DRAFT),
                \Filament\Forms\Components\DatePicker::make('received_at')
                    ->label('Date de réception'),
                \Filament\Forms\Components\TextInput::make('quality_rating')
                    ->label('Note de qualité (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                \Filament\Forms\Components\Toggle::make('has_litigation')
                    ->label('Litige en cours ?'),
            ]);
    }
}
