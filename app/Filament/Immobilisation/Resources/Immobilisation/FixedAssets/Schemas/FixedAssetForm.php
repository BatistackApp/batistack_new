<?php

namespace App\Filament\Immobilisation\Resources\Immobilisation\FixedAssets\Schemas;

use App\Enums\Immobilisation\AssetStatus;
use App\Enums\Immobilisation\DepreciationMethod;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FixedAssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                \Filament\Forms\Components\Select::make('asset_category_id')
                    ->label('Catégorie d\'actif')
                    ->relationship('category', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Nom de l\'immobilisation')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('serial_number')
                    ->label('Numéro de série')
                    ->maxLength(255),
                \Filament\Forms\Components\DatePicker::make('purchase_date')
                    ->label('Date d\'acquisition')
                    ->required(),
                \Filament\Forms\Components\TextInput::make('purchase_price')
                    ->label('Valeur d\'achat')
                    ->required()
                    ->numeric()
                    ->prefix('€'),
                \Filament\Forms\Components\TextInput::make('salvage_value')
                    ->label('Valeur résiduelle')
                    ->numeric()
                    ->default(0)
                    ->prefix('€'),
                \Filament\Forms\Components\Select::make('depreciation_method')
                    ->label('Méthode d\'amortissement')
                    ->options(\App\Enums\Immobilisation\DepreciationMethod::class)
                    ->required()
                    ->default(\App\Enums\Immobilisation\DepreciationMethod::LINEAR),
                \Filament\Forms\Components\TextInput::make('useful_life_years')
                    ->label('Durée d\'amortissement (années)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(5),
                \Filament\Forms\Components\Select::make('status')
                    ->label('Statut')
                    ->options(\App\Enums\Immobilisation\AssetStatus::class)
                    ->default(\App\Enums\Immobilisation\AssetStatus::ACTIVE)
                    ->required(),
                \Filament\Forms\Components\Select::make('supplier_invoice_id')
                    ->label('Facture d\'achat liée')
                    ->relationship('supplierInvoice', 'reference')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Select::make('vehicle_id')
                    ->label('Véhicule lié')
                    ->relationship('vehicle', 'license_plate')
                    ->searchable()
                    ->preload(),
                \Filament\Forms\Components\Select::make('chantier_id')
                    ->label('Chantier d\'imputation analytique')
                    ->relationship('chantier', 'name')
                    ->searchable()
                    ->preload(),
            ]);
    }
}
