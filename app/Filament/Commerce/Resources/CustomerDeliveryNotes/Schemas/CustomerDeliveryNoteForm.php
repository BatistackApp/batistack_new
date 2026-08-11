<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Schemas;

use Filament\Schemas\Schema;

class CustomerDeliveryNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('client_id')->label('Client')
                    ->label('Client')
                    ->relationship('client', 'name')
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\Select::make('chantier_id')->label('Chantier')
                    ->label('Chantier')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\Select::make('customer_order_id')
                    ->label('Commande Client')
                    ->relationship('order', 'reference')
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
                \Filament\Forms\Components\DatePicker::make('delivery_date')
                    ->label('Date de livraison'),
                \Filament\Forms\Components\Select::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('user', 'name')
                    ->required(),
            ]);
    }
}
