<?php

namespace App\Filament\Interventions\Schemas;

use App\Enums\Interventions\MaintenanceContractFrequency;
use App\Enums\Interventions\MaintenanceContractStatus;
use App\Models\Interventions\ClientEquipment;
use App\Models\Tiers\ThirdParty;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MaintenanceContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Contrat d\'entretien')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('third_party_id')
                            ->label('Client')
                            ->options(ThirdParty::clients()->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Select::make('client_equipment_id')
                            ->label('Équipement client')
                            ->options(fn (callable $get) => ClientEquipment::where('third_party_id', $get('third_party_id'))->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (callable $get) => blank($get('third_party_id')))
                            ->helperText(fn (callable $get) => blank($get('third_party_id')) ? 'Sélectionnez d\'abord le client.' : null),
                        Select::make('chantier_id')
                            ->label('Chantier (optionnel)')
                            ->relationship('chantier', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        TextInput::make('name')
                            ->label('Nom du contrat')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('ex: Contrat annuel groupe frigorifique'),
                        Select::make('frequency')
                            ->label('Fréquence')
                            ->options(MaintenanceContractFrequency::class)
                            ->default(MaintenanceContractFrequency::ANNUAL)
                            ->required(),
                        Select::make('status')
                            ->label('Statut')
                            ->options(MaintenanceContractStatus::class)
                            ->default(MaintenanceContractStatus::ACTIVE)
                            ->required(),
                        TextInput::make('flat_rate_price')
                            ->label('Prix forfaitaire HT')
                            ->numeric()
                            ->required()
                            ->prefix('€')
                            ->default(0),
                        DatePicker::make('start_date')
                            ->label('Début du contrat')
                            ->required()
                            ->default(now()),
                        DatePicker::make('end_date')
                            ->label('Fin du contrat (optionnel)')
                            ->after('start_date')
                            ->nullable(),
                        DatePicker::make('next_due_date')
                            ->label('Prochaine échéance')
                            ->helperText('Si vide, aucune intervention ne sera générée tant que la date n\'est pas renseignée.'),
                        Textarea::make('description')
                            ->label('Description de la prestation')
                            ->rows(3)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Notes internes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
