<?php

namespace App\Filament\Articles\Resources\Items\Schemas;

use App\Enums\Articles\GhsPictogram;
use App\Enums\Articles\HazardCategory;
use App\Enums\Articles\ItemType;
use App\Enums\Tiers\ThirdPartyType;
use App\Models\Core\Unit;
use App\Models\Core\VatRate;
use Ariefng\FilamentCalculator\Actions\CalculatorAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
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
                                        TextInput::make('reference')->label('Référence')
                                            ->label('Référence SKU')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('ex: TOLEP_R7016'),
                                        BarcodeInput::make('barcode')
                                            ->label('Code-barres / EAN')
                                            ->nullable()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('Scanner ou taper le code...'),
                                        TextInput::make('name')->label('Nom')
                                            ->label('Désignation')
                                            ->required()
                                            ->maxLength(255),
                                        Select::make('type')->label('Type')
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
                                        Textarea::make('description')->label('Description')
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
                                            ->suffixAction(CalculatorAction::make())
                                            ->disabled(fn (Get $get) => $get('type') === ItemType::WORK->value)
                                            ->helperText('Pour les ouvrages, le coût est calculé dynamiquement.'),
                                        TextInput::make('selling_price')
                                            ->label('Prix de vente HT')
                                            ->numeric()
                                            ->prefix('€')
                                            ->suffixAction(CalculatorAction::make())
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
                                        Toggle::make('is_sensitive')
                                            ->label('Article sensible')
                                            ->helperText('Activer la traçabilité par lot et date de péremption.')
                                            ->onColor('warning'),
                                    ]),
                            ]),

                        // --- SÉCURITÉ & FDS ---
                        Tabs\Tab::make('Sécurité & FDS')
                            ->icon(Phosphor::HardHat)
                            ->schema([
                                Section::make('Fiche de données de sécurité (FDS)')
                                    ->description('Renseignez les dangers CLP pour permettre la génération automatique du PPSPS.')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('hazard_category')
                                            ->label('Catégorie de danger')
                                            ->options(HazardCategory::class)
                                            ->placeholder('Aucun danger')
                                            ->nullable()
                                            ->native(false),
                                        DatePicker::make('fds_updated_at')
                                            ->label('Date de mise à jour de la FDS'),
                                        CheckboxList::make('ghs_pictograms')
                                            ->label('Pictogrammes CLP')
                                            ->options(GhsPictogram::class)
                                            ->columns(2)
                                            ->columnSpanFull(),
                                        TagsInput::make('h_phrases')
                                            ->label('Phrases de danger (H)')
                                            ->helperText('Liste libre, ex: H225 Liquide et vapeurs très inflammables')
                                            ->columnSpanFull(),
                                        TagsInput::make('p_phrases')
                                            ->label('Phrases de précaution (P)')
                                            ->helperText('Liste libre, ex: P210 Tenir à l\'écart de la chaleur')
                                            ->columnSpanFull(),
                                        SpatieMediaLibraryFileUpload::make('fds_document')
                                            ->label('Fiche de données de sécurité (PDF)')
                                            ->collection('fds_document')
                                            ->columnSpanFull(),
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
