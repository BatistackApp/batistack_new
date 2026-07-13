<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Schemas;

use Filament\Schemas\Schema;

class CustomerDeliveryNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('client_id')
                    ->relationship('client', 'name')
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\Select::make('chantier_id')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\Select::make('customer_order_id')
                    ->relationship('order', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('reference')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\Select::make('status')
                    ->options(\App\Enums\Commerce\DeliveryStatus::class)
                    ->required()
                    ->default(\App\Enums\Commerce\DeliveryStatus::DRAFT),
                \Filament\Forms\Components\DatePicker::make('delivery_date'),
                \Filament\Forms\Components\Select::make('responsable_id')
                    ->relationship('user', 'name')
                    ->required(),
            ]);
    }
}
