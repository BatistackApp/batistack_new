<?php

namespace App\Filament\Flottes\Resources\Vehicles\Schemas;

use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Fiche de l\'Actif')
                    ->tabs([
                        // --- ONGLET 1 : IDENTITÉ & LÉGAL (ZFE, POLLUTION, CRIT'AIR) ---
                        Tabs\Tab::make('Identité & Légal')
                            ->icon(Phosphor::IdentificationCard)
                            ->schema([
                                Section::make()
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('reference')->label('Référence')
                                            ->label('Code Interne')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('ex: VUL-001')
                                            ->maxLength(50),
                                        TextInput::make('license_plate')
                                            ->label('Immatriculation')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('ex: AA-123-BB')
                                            ->maxLength(20),
                                        Select::make('status')->label('Statut')
                                            ->label('Statut Opérationnel')
                                            ->options(VehicleStatus::class)
                                            ->required()
                                            ->native(false)
                                            ->default(VehicleStatus::AVAILABLE),
                                    ]),

                                Section::make()
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('brand')
                                            ->label('Marque')
                                            ->required()
                                            ->placeholder('ex: Peugeot'),
                                        TextInput::make('model')
                                            ->label('Modèle')
                                            ->required()
                                            ->placeholder('ex: Partner'),
                                        Select::make('type')->label('Type')
                                            ->label('Catégorie d\'actif')
                                            ->options(VehicleType::class)
                                            ->required()
                                            ->native(false)
                                            ->live(),
                                    ]),

                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('crit_air_level')
                                            ->label('Vignette Crit\'Air (Norme ZFE)')
                                            ->options([
                                                'E' => 'Crit\'Air E (Zéro Émission)',
                                                '1' => 'Crit\'Air 1',
                                                '2' => 'Crit\'Air 2',
                                                '3' => 'Crit\'Air 3',
                                                '4' => 'Crit\'Air 4',
                                                '5' => 'Crit\'Air 5',
                                            ])
                                            ->native(false)
                                            ->placeholder('Sélectionnez la vignette...'),
                                        DatePicker::make('pollution_control_due_at')
                                            ->label('Échéance Contrôle Pollution (VUL)')
                                            ->native(false)
                                            ->placeholder('Visite pollution complémentaire annuelle')
                                            ->visible(fn (Get $get) => $get('type') === VehicleType::UTILITY->value),
                                    ]),
                            ]),

                        // --- ONGLET 2 : CONFIGURATION TECHNIQUE (KM VS HORAMÈTRE ENGIN) ---
                        Tabs\Tab::make('Usage & Carburant')
                            ->icon(Phosphor::Gauge)
                            ->schema([
                                Section::make()
                                    ->columns(3)
                                    ->schema([
                                        Select::make('usage_unit')
                                            ->label('Unité d\'usage de l\'actif')
                                            ->options([
                                                'km' => 'Odomètre (Kilomètres - Routiers standard)',
                                                'hours' => 'Horamètre (Heures moteur - Engins de chantiers)',
                                            ])
                                            ->required()
                                            ->default('km')
                                            ->native(false)
                                            ->live(),
                                        TextInput::make('odometer')
                                            ->label('Valeur courante du compteur')
                                            ->required()
                                            ->numeric()
                                            ->default(0)
                                            ->suffix(fn (Get $get) => $get('usage_unit') === 'hours' ? 'h' : 'km'),
                                        TextInput::make('fuel_type')
                                            ->label('Type de Carburant / Énergie')
                                            ->required()
                                            ->placeholder('ex: Diesel, Gazole, Électrique, Essence'),
                                    ]),
                                Section::make()
                                    ->schema([
                                        TextInput::make('current_location')
                                            ->label('Lieu de stationnement habituel / Dépôt de rattachement')
                                            ->placeholder('ex: Dépôt Principal Nord, Site Nantes')
                                            ->maxLength(255),
                                    ]),
                            ]),

                        // --- ONGLET 3 : ANALYTIQUE, ACQUISITION & TCO ---
                        Tabs\Tab::make('Acquisition & TCO')
                            ->icon(Phosphor::CurrencyEur)
                            ->schema([
                                Section::make()
                                    ->columns(3)
                                    ->schema([
                                        DatePicker::make('purchase_date')
                                            ->label('Date d\'acquisition')
                                            ->native(false),
                                        TextInput::make('purchase_price')
                                            ->label('Prix d\'acquisition')
                                            ->numeric()
                                            ->prefix('€'),
                                        TextInput::make('tco_cache')
                                            ->label('Cache analytique TCO')
                                            ->numeric()
                                            ->prefix('€')
                                            ->disabled()
                                            ->helperText('Amorti dynamiquement selon les charges réelles de l\'actif (Maintenance, Assurance, Péages).'),
                                    ]),

                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('daily_rate')
                                            ->label('Coût journalier interne de mise à disposition')
                                            ->numeric()
                                            ->prefix('€')
                                            ->helperText('Utilisé pour l\'analyse de marge de chantiers'),
                                        TextInput::make('km_rate')
                                            ->label('Coût unitaire kilométrique (ou horaire)')
                                            ->numeric()
                                            ->step(0.0001)
                                            ->prefix('€')
                                            ->helperText('ex: 0.4500 € pour l\'imputation kilométrique'),
                                    ]),
                            ]),

                        // --- ONGLET 4 : CARTES GRISES & PHOTOS ---
                        Tabs\Tab::make('Galerie & Documents')
                            ->icon(Phosphor::Files)
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('registration_card')
                                    ->label('Carte Grise / Certificat d\'Immatriculation (PDF / Image)')
                                    ->collection('registration_card')
                                    ->maxFiles(1),
                                SpatieMediaLibraryFileUpload::make('photos')
                                    ->label('Galerie photos de l\'état de la carrosserie')
                                    ->collection('photos')
                                    ->multiple()
                                    ->image(),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
