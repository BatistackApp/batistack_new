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
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Models\Chantiers\Chantier;
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
                                        TextInput::make('reference')->label('Référence')
                                            ->label('Référence Chantier')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->default(function () {
                                                $lastChantier = Chantier::whereYear('created_at', now()->year)->latest('id')->first();
                                                if ($lastChantier && preg_match('/CH-\d{4}-(\d+)/', $lastChantier->reference, $matches)) {
                                                    $lastNumber = (int) $matches[1];
                                                    return 'CH-' . now()->year . '-' . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
                                                }
                                                return 'CH-' . now()->year . '-001';
                                            })
                                            ->placeholder('ex: CH-2026-001'),
                                        Select::make('status')->label('Statut')
                                            ->label('Statut Opérationnel')
                                            ->options(ChantierStatus::class)
                                            ->required()
                                            ->native(false),
                                        TextInput::make('name')->label('Nom')
                                            ->label('Désignation du projet')
                                            ->required()
                                            ->columnSpanFull(),
                                        Select::make('client_id')->label('Client')
                                            ->label('Client (Maître d\'ouvrage)')
                                            ->relationship('client', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),
                                        Select::make('quote_id')
                                            ->label('Devis d\'origine')
                                            ->relationship('quote', 'reference')
                                            ->searchable()
                                            ->preload(),
                                        Select::make('manager_id')
                                            ->label('Conducteur de travaux')
                                            ->relationship('manager', 'last_name')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name . ($record->currentContract ? ' - ' . $record->currentContract->job_title : ''))
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
                                            DatePicker::make('start_date_preview')
                                                ->label('Démarrage prévu')
                                                ->native(false)
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if ($get('end_date_preview') && $state) {
                                                        $startDate = Carbon::parse($state);
                                                        $endDate = Carbon::parse($get('end_date_preview'));

                                                        if ($endDate->lt($startDate)) {
                                                            $set('budget_hours', 0);

                                                            return;
                                                        }

                                                        $workingDays = 0;
                                                        // Itérer sur chaque jour de la période pour compter les jours ouvrables
                                                        while ($startDate->lte($endDate)) {
                                                            if (! $startDate->isWeekend()) {
                                                                $workingDays++;
                                                            }
                                                            $startDate->addDay();
                                                        }
                                                        $set('budget_hours', $workingDays * 8);
                                                    }
                                                })
                                                ->reactive()
                                                ->live(),
                                            DatePicker::make('end_date_preview')
                                                ->label('Fin prévue')
                                                ->native(false)
                                                ->afterStateUpdated(function ($state, $set, $get) {
                                                    if ($get('start_date_preview') && $state) {
                                                        $startDate = Carbon::parse($state);
                                                        $endDate = Carbon::parse($get('start_date_preview'));

                                                        if ($endDate->lt($startDate)) {
                                                            $set('budget_hours', 0);

                                                            return;
                                                        }

                                                        $workingDays = 0;
                                                        // Itérer sur chaque jour de la période pour compter les jours ouvrables
                                                        while ($startDate->lte($endDate)) {
                                                            if (! $startDate->isWeekend()) {
                                                                $workingDays++;
                                                            }
                                                            $startDate->addDay();
                                                        }
                                                        $set('budget_hours', $workingDays * 8);
                                                    }
                                                })
                                                ->reactive()
                                                ->live(),
                                        ])->columnSpan(1),
                                    Section::make('Enveloppes HT')
                                        ->schema([
                                            TextInput::make('budget_hours')
                                                ->label('Budget Heures')
                                                ->numeric()
                                                ->readOnly()
                                                ->suffix('h'),
                                            TextInput::make('budget_total_ht')->label('Montant Global Marché')->numeric()->prefix('€'),
                                        ])->columnSpan(1),
                                ]),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }
}
