<?php

namespace App\Filament\Commerce\Resources\CustomerSituations\Schemas;

use Filament\Schemas\Schema;

class CustomerSituationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('customer_order_id')
                    ->relationship('order', 'reference')
                    ->required()
                    ->searchable(),
                \Filament\Forms\Components\Select::make('chantier_id')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                \Filament\Forms\Components\TextInput::make('number')
                    ->required()
                    ->numeric(),
                \Filament\Forms\Components\Select::make('status')
                    ->options(\App\Enums\Commerce\InvoiceStatus::class)
                    ->required()
                    ->default(\App\Enums\Commerce\InvoiceStatus::DRAFT),
                \Filament\Forms\Components\TextInput::make('retenue_garantie_amount')
                    ->numeric(),
                \Filament\Forms\Components\TextInput::make('prorata_amount')
                    ->numeric(),
                \Filament\Forms\Components\DatePicker::make('periode_start'),
                \Filament\Forms\Components\DatePicker::make('periode_end'),
                \Filament\Forms\Components\Select::make('responsable_id')
                    ->relationship('user', 'name')
                    ->required(),
            ]);
    }
}
