<?php

namespace App\Filament\Commerce\Resources\CustomerDeliveryNotes\Schemas;

use App\Enums\Commerce\DeliveryStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerDeliveryNoteForm
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
                Select::make('chantier_id')->label('Chantier')
                    ->label('Chantier')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                Select::make('customer_order_id')
                    ->label('Commande Client')
                    ->relationship('order', 'reference')
                    ->searchable(),
                TextInput::make('reference')->label('Référence')
                    ->label('Référence')
                    ->required()
                    ->maxLength(255),
                Select::make('status')->label('Statut')
                    ->label('Statut')
                    ->options(DeliveryStatus::class)
                    ->required()
                    ->default(DeliveryStatus::DRAFT),
                DatePicker::make('delivery_date')
                    ->label('Date de livraison'),
                Select::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('user', 'name')
                    ->required(),
            ]);
    }
}
