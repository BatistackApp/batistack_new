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
                    ->label('Commande Client')
                    ->relationship('order', 'reference')
                    ->required()
                    ->searchable(),
                Select::make('chantier_id')
                    ->label('Chantier')
                    ->relationship('chantier', 'reference')
                    ->searchable(),
                TextInput::make('number')
                    ->label('Numéro')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->label('Statut')
                    ->options(InvoiceStatus::class)
                    ->required()
                    ->default(InvoiceStatus::DRAFT),
                TextInput::make('retenue_garantie_amount')
                    ->label('Montant de la retenue de garantie')
                    ->numeric(),
                TextInput::make('prorata_amount')
                    ->label('Montant du compte prorata')
                    ->numeric(),
                DatePicker::make('periode_start')
                    ->label('Début de période'),
                DatePicker::make('periode_end')
                    ->label('Fin de période'),
                Select::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('user', 'name')
                    ->required(),
            ]);
    }
}
