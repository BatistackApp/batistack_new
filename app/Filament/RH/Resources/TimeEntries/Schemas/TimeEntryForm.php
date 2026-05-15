<?php

namespace App\Filament\RH\Resources\TimeEntries\Schemas;

use App\Enums\RH\TimeEntryType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TimeEntryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Saisie hebdomadaire des heures')
                    ->description('Sélectionnez la semaine et renseignez les heures de travail et de trajet pour le chantier sélectionné.')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('employee_id')
                                    ->label('Salarié')
                                    ->relationship('employee', 'last_name')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->disabledOn('edit'),

                                Select::make('job_site_id')
                                    ->label('Chantier / Projet')
                                    ->placeholder('Rechercher un chantier...')
                                    ->required()
                                    ->searchable(),
                            ]),

                        Grid::make(3)
                            ->schema([
                                DatePicker::make('week_start')
                                    ->label('Semaine du (Lundi)')
                                    ->default(now()->startOfWeek())
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),

                                Select::make('type')
                                    ->label('Nature des heures')
                                    ->options(TimeEntryType::class)
                                    ->default(TimeEntryType::NORMAL)
                                    ->required()
                                    ->native(false),
                            ]),

                        Grid::make(1)
                            ->schema([
                                // --- GRILLE TRAVAIL ---
                                Section::make('Temps de Travail Effectif')
                                    ->description('Heures de production sur site')
                                    ->compact()
                                    ->schema([
                                        Grid::make(7)
                                            ->schema([
                                                TextInput::make('monday_hours')->label('Lun')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('tuesday_hours')->label('Mar')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('wednesday_hours')->label('Mer')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('thursday_hours')->label('Jeu')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('friday_hours')->label('Ven')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('saturday_hours')->label('Sam')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('sunday_hours')->label('Dim')->numeric()->minValue(0)->step(0.25)->default(0),
                                            ]),
                                    ]),

                                // --- GRILLE TRAJET ---
                                Section::make('Temps de Trajet')
                                    ->description('Heures de route (Aller/Retour)')
                                    ->compact()
                                    ->schema([
                                        Grid::make(7)
                                            ->schema([
                                                TextInput::make('monday_travel')->label('Lun')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('tuesday_travel')->label('Mar')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('wednesday_travel')->label('Mer')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('thursday_travel')->label('Jeu')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('friday_travel')->label('Ven')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('saturday_travel')->label('Sam')->numeric()->minValue(0)->step(0.25)->default(0),
                                                TextInput::make('sunday_travel')->label('Dim')->numeric()->minValue(0)->step(0.25)->default(0),
                                            ]),
                                    ]),
                            ])->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Commentaires de la semaine')
                            ->columnSpanFull()
                            ->placeholder('Description succincte des travaux réalisés durant la semaine...'),
                    ])
            ]);
    }
}
