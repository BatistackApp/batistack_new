<?php

namespace App\Filament\Locations\Resources\Locations\SupplierPriceGrids\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SupplierPriceGridForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'name')
                    ->label('Fournisseur')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('equipment_category')
                    ->label('Catégorie / Type d\'équipement')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('daily_rate')
                    ->label('Tarif Journalier')
                    ->numeric()
                    ->minValue(0)
                    ->requiredWithoutAll('weekly_rate,monthly_rate')
                    ->prefix('€'),
                \Filament\Forms\Components\TextInput::make('weekly_rate')
                    ->label('Tarif Hebdomadaire')
                    ->numeric()
                    ->minValue(0)
                    ->requiredWithoutAll('daily_rate,monthly_rate')
                    ->prefix('€'),
                \Filament\Forms\Components\TextInput::make('monthly_rate')
                    ->label('Tarif Mensuel')
                    ->numeric()
                    ->minValue(0)
                    ->requiredWithoutAll('daily_rate,weekly_rate')
                    ->prefix('€'),
            ]);
    }
}
