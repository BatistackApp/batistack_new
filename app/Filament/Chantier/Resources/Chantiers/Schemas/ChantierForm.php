<?php

namespace App\Filament\Chantier\Resources\Chantiers\Schemas;

use App\Enums\Chantiers\ChantierStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ChantierForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Détails du Chantier')
                    ->tabs([
                        Tabs\Tab::make('Identité & Lieu')
                            ->icon(Phosphor::IdentificationCard)
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('reference')
                                            ->label('Référence Chantier')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('ex: CH-2026-001'),
                                        Select::make('status')
                                            ->label('Statut Opérationnel')
                                            ->options(ChantierStatus::class)
                                            ->required()
                                            ->native(false),
                                        TextInput::make('name')
                                            ->label('Désignation du projet')
                                            ->required()
                                            ->columnSpanFull(),
                                        Select::make('client_id')
                                            ->label('Client (Maître d\'ouvrage)')
                                            ->relationship('client', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Select::make('manager_id')
                                            ->label('Conducteur de travaux')
                                            ->relationship('manager', 'last_name')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                            ->searchable()
                                            ->preload(),
                                    ]),
                                Section::make('Localisation')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('address')->label('Adresse')->required()->columnSpan(2),
                                        TextInput::make('zip_code')->label('Code Postal')->required(),
                                        TextInput::make('city')->label('Ville')->required()->columnSpan(2),
                                    ]),
                            ]),

                        Tabs\Tab::make('Planning & Budgets')
                            ->icon(Phosphor::CalendarCheck)
                            ->schema([
                                Grid::make(2)->schema([
                                    Section::make('Dates Prévisionnelles')
                                        ->schema([
                                            DatePicker::make('start_date_preview')->label('Démarrage prévu')->native(false),
                                            DatePicker::make('end_date_preview')->label('Fin prévue')->native(false),
                                        ])->columnSpan(1),
                                    Section::make('Enveloppes HT')
                                        ->schema([
                                            TextInput::make('budget_hours')->label('Budget Heures')->numeric()->suffix('h'),
                                            TextInput::make('budget_total_ht')->label('Montant Global Marché')->numeric()->prefix('€'),
                                        ])->columnSpan(1),
                                ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
