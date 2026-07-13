<?php

namespace App\Filament\Commerce\Resources\CustomerSituations\Schemas;

use App\Enums\Commerce\InvoiceStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerSituationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_order_id')
                    ->relationship('order', 'reference')
                    ->required()
                    ->searchable(),
                Select::make('chantier_id')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                TextInput::make('number')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options(InvoiceStatus::class)
                    ->required()
                    ->default(InvoiceStatus::DRAFT),
                TextInput::make('retenue_garantie_amount')
                    ->numeric(),
                TextInput::make('prorata_amount')
                    ->numeric(),
                DatePicker::make('periode_start'),
                DatePicker::make('periode_end'),
                Select::make('responsable_id')
                    ->relationship('user', 'name')
                    ->required(),
            ]);
    }
}
