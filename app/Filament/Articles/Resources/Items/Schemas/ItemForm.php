<?php

namespace App\Filament\Articles\Resources\Items\Schemas;

use App\Enums\Articles\ItemType;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Marcelorodrigo\FilamentBarcodeScannerField\Forms\Components\BarcodeInput;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Fiche Produit')
                    ->tabs([
                        // --- GÉNÉRAL ---
                        Tabs\Tab::make('Informations de base')
                            ->icon(Phosphor::Info)
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('reference')
                                            ->label('Référence SKU')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('ex: TOLEP_R7016'),
                                        BarcodeInput::make('barcode')
                                            ->label('Code-barres / EAN')
                                            ->nullable()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('Scanner ou taper le code...'),
                                        TextInput::make('name')
                                            ->label('Désignation')
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('type')
                                            ->label('Nature de l\'article')
                                            ->options(ItemType::class)
                                            ->required()
                                            ->live()
                                            ->native(false),
                                        Select::make('unit_id')
                                            ->label('Unité de mesure')
                                            ->options(Unit::pluck('name', 'id'))
                                            ->required()
                                            ->native(false),
                                        Select::make('supplier_id')
                                            ->label('Fournisseur principal')
                                            ->relationship('supplier', 'name', fn ($query) => $query->where('type', ThirdPartyType::SUPPLIER))
                                            ->searchable()
                                            ->preload()
                                            ->nullable(),
                                        Textarea::make('description')
                                            ->label('Description technique')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // --- TARIFICATION ---
                        Tabs\Tab::make('Tarification & Taxes')
                            ->icon(Phosphor::CurrencyEur)
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('purchase_price')
                                            ->label(fn (Get $get) => $get('type') === ItemType::WORK->value ? 'Coût de revient estimé' : 'Prix d\'achat / PUMP')
                                            ->numeric()
                                            ->prefix('€')
                                            ->disabled(fn (Get $get) => $get('type') === ItemType::WORK->value)
                                            ->helperText('Pour les ouvrages, le coût est calculé dynamiquement.'),
                                        TextInput::make('selling_price')
                                            ->label('Prix de vente HT')
                                            ->numeric()
                                            ->prefix('€')
                                            ->required(),
                                        Select::make('vat_rate_id')
                                            ->label('Taux de TVA')
                                            ->options(VatRate::pluck('name', 'id'))
                                            ->required()
                                            ->native(false),
                                        Toggle::make('is_active')
                                            ->label('Visible dans le catalogue')
                                            ->default(true)
                                            ->onColor('success'),
                                    ]),
                            ]),

                        // --- MÉDIAS ---
                        Tabs\Tab::make('Visuels & Documents')
                            ->icon(Phosphor::Image)
                            ->schema([
                                Section::make('Galerie')
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('image')
                                            ->label('Photo principale')
                                            ->collection('primary_image')
                                            ->image(),
                                        SpatieMediaLibraryFileUpload::make('technical_sheets')
                                            ->label('Fiches techniques (PDF)')
                                            ->collection('docs')
                                            ->multiple(),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
