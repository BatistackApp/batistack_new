<?php

namespace App\Filament\Flottes\Resources\TrafficFines\Schemas;

use App\Enums\Flottes\FineStatus;
use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TrafficFineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations sur l\'infraction')
                    ->columns(2)
                    ->schema([
                        TextInput::make('reference')
                            ->label('Numéro d\'avis de contravention')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('ex: 375123456789'),

                        Select::make('vehicle_id')
                            ->label('Véhicule concerné')
                            ->relationship('vehicle', 'reference')
                            ->getOptionLabelFromRecordUsing(fn (Vehicle $record) => "{$record->brand} {$record->model} ({$record->license_plate})")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('employee_id', null)),

                        DateTimePicker::make('infraction_at')
                            ->label('Date & Heure de l\'infraction')
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('employee_id', null)),

                        TextInput::make('amount')
                            ->label('Montant de l\'amende forfaitaire')
                            ->numeric()
                            ->prefix('€')
                            ->required(),

                        TextInput::make('points_deducted')
                            ->label('Retrait de points prévu')
                            ->numeric()
                            ->default(0),

                        Select::make('status')
                            ->label('Statut du dossier')
                            ->options(FineStatus::class)
                            ->required()
                            ->native(false)
                            ->default(FineStatus::RECEIVED),
                    ]),

                Section::make('Attribution du Conducteur Responsable')
                    ->description('L\'ERP résout automatiquement l\'identité du conducteur à partir de l\'historique d\'affectation du véhicule à ce moment précis.')
                    ->schema([
                        Select::make('employee_id')
                            ->label('Conducteur désigné responsable')
                            ->relationship('employee', 'last_name')
                            ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->placeholder('Identification automatique en cours...')
                            ->helperText('Si laissé vide, l\'algorithme de Batistack résoudra automatiquement le chauffeur lors de la sauvegarde.'),
                    ]),
            ]);
    }
}
