<?php

namespace App\Filament\RH\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Dossier Employé')
                    ->tabs([
                        // --- IDENTITÉ ---
                        Tabs\Tab::make('État Civil')
                            ->icon(Phosphor::IdentificationCard)
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('registration_number')
                                            ->label('Matricule')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('ex: MAT-2025-001'),
                                        Toggle::make('is_active')
                                            ->label('Salarié en poste')
                                            ->default(true)
                                            ->onColor('success'),
                                        TextInput::make('first_name')
                                            ->label('Prénom')
                                            ->required(),
                                        TextInput::make('last_name')
                                            ->label('Nom')
                                            ->required(),
                                        DatePicker::make('birth_date')
                                            ->label('Date de naissance')
                                            ->native(false),
                                        TextInput::make('social_security_number')
                                            ->label('N° Sécurité Sociale')
                                            ->mask('9 99 99 99 999 999 99')
                                            ->placeholder('1 85 06 ...'),
                                    ]),
                            ]),

                        // --- CONTACT ---
                        Tabs\Tab::make('Contact & Urgence')
                            ->icon(Phosphor::Phone)
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('email')
                                            ->label('Email professionnel')
                                            ->required()
                                            ->helperText('Sera utilisé pour la connexion à Batistack.')
                                            ->email(),
                                        TextInput::make('phone')
                                            ->label('Téléphone')
                                            ->tel(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Adresse Postal')
                            ->icon(Phosphor::MapPinArea)
                            ->schema([
                                Textarea::make('address')
                                    ->label('Adresse')
                                    ->rows(2)
                                    ->required(),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('postal_code')
                                            ->label('Code Postal')
                                            ->required()
                                            ->mask('99999'),
                                        TextInput::make('city')
                                            ->label('Ville')
                                            ->columnSpan(2)
                                            ->required(),
                                    ]),
                            ]),

                        // --- MÉDIAS & BIOMÉTRIE ---
                        Tabs\Tab::make('Documents, Photo & Biométrie')
                            ->icon(Phosphor::Fingerprint)
                            ->schema([
                                Section::make('Biométrie & RGPD')
                                    ->schema([
                                        Toggle::make('biometric_consent')
                                            ->label('Consentement Biométrique RGPD')
                                            ->helperText('L\'employé a signé l\'accord autorisant l\'utilisation de son image pour la pointeuse kiosque.')
                                            ->onColor('success'),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        SpatieMediaLibraryFileUpload::make('avatar')
                                            ->label('Photo de profil (Sert de base pour la reconnaissance faciale)')
                                            ->collection('avatar')
                                            ->image()
                                            ->avatar()
                                            ->imageEditor(),
                                        SpatieMediaLibraryFileUpload::make('id_docs')
                                            ->label('Pièces d\'identité / Titre de séjour')
                                            ->collection('identity_docs')
                                            ->multiple(),
                                    ]),
                            ]),

                        // --- PAIE ---
                        Tabs\Tab::make('Paie')
                            ->icon(Phosphor::CurrencyEur)
                            ->schema([
                                Section::make('Prélèvement à la Source (PAS)')
                                    ->description('Renseignez ici le taux de PAS communiqué par l\'administration fiscale. Ce taux sera automatiquement utilisé lors de la génération des bulletins de paie.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('pas_rate')
                                            ->label('Taux PAS (%)')
                                            ->numeric()
                                            ->default(0)
                                            ->suffix('%')
                                            ->step(0.01)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->helperText('Taux personnalisé transmis par le service des impôts (ex: 7.50).'),
                                    ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
