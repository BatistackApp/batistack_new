<?php

namespace App\Filament\Flottes\Resources\VehicleAssignments\Schemas;

use App\Models\Flottes\Vehicle;
use App\Models\RH\Employee;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Planification du Trajet')
                    ->columns(2)
                    ->schema([
                        Select::make('vehicle_id')
                            ->label('Véhicule / Engin mobilisé')
                            ->relationship('vehicle', 'reference')
                            ->getOptionLabelFromRecordUsing(fn (Vehicle $record) => "{$record->reference} - {$record->brand} {$record->model} ({$record->license_plate})")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),

                        Select::make('employee_id')
                            ->label('Conducteur responsable (Titulaire de la garde)')
                            ->relationship('employee', 'last_name')
                            ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->full_name)
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),

                        Select::make('chantier_id')
                            ->label('Chantier d\'imputation analytique')
                            ->relationship('chantier', 'name')
                            ->searchable()
                            ->preload(),

                        TextInput::make('purpose')
                            ->label('Objet du déplacement')
                            ->placeholder('ex: Acheminement outillage lourd et équipe pose')
                            ->maxLength(255),

                        DateTimePicker::make('started_at')
                            ->label('Début d\'affectation')
                            ->required()
                            ->native(false),

                        DateTimePicker::make('ended_at')
                            ->label('Restitution prévue')
                            ->native(false),
                    ]),

                Section::make('Co-voiturage d\'Équipage de Chantier')
                    ->description('Désignez les compagnons passagers du voyage pour imputer plus tard leurs indemnités collectives.')
                    ->schema([
                        Select::make('passengers')
                            ->label('Passagers compagnons')
                            ->relationship('passengers', 'last_name')
                            ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->full_name)
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }
}
