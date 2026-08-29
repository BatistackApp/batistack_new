<?php

namespace App\Filament\Locations\Resources\InternalRentalInvoices\Schemas;

use App\Enums\Locations\InternalRentalInvoiceStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InternalRentalInvoiceForm
{
    public static function configure(Schema $schema, bool $isRelationManager = false): Schema
    {
        $components = [];

        if (! $isRelationManager) {
            $components[] = Select::make('fixed_asset_id')
                ->label('Actif / Machine')
                ->relationship('fixedAsset', 'name')
                ->searchable()
                ->preload()
                ->required();
        }

        $components = array_merge($components, [
            Select::make('chantier_id')
                ->label('Chantier d\'imputation')
                ->relationship('chantier', 'name')
                ->searchable()
                ->preload()
                ->required(),
            DatePicker::make('period_start')
                ->label('Début de période')
                ->required(),
            DatePicker::make('period_end')
                ->label('Fin de période')
                ->required(),
            TextInput::make('days')
                ->label('Nombre de jours')
                ->numeric()
                ->minValue(1)
                ->required(),
            TextInput::make('daily_rate')
                ->label('Tarif journalier')
                ->numeric()
                ->prefix('€')
                ->required(),
            TextInput::make('amount_ht')
                ->label('Montant HT')
                ->numeric()
                ->prefix('€')
                ->required(),
            Select::make('status')
                ->label('Statut')
                ->options(InternalRentalInvoiceStatus::class)
                ->default(InternalRentalInvoiceStatus::DRAFT)
                ->required(),
            TextInput::make('billing_key')
                ->label('Clé de facturation')
                ->maxLength(255)
                ->disabled()
                ->dehydrated(),
        ]);

        return $schema->components($components);
    }
}
