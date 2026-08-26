<?php

namespace App\Filament\Commerce\Resources\ReceiptNotes\Schemas;

use App\Enums\Commerce\DeliveryStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ReceiptNoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('purchase_order_id')
                    ->label('Commande Fournisseur')
                    ->relationship('order', 'reference')
                    ->searchable(),
                Select::make('warehouse_id')
                    ->label('Entrepôt')
                    ->relationship('warehouse', 'name')
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
                DatePicker::make('received_at')
                    ->label('Date de réception'),
                TextInput::make('quality_rating')
                    ->label('Note de qualité (1-5)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5),
                Toggle::make('has_litigation')
                    ->label('Litige en cours ?'),
            ]);
    }
}
