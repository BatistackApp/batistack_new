<?php

namespace App\Filament\Flottes\Widgets;

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Flottes\VehicleAssignment;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ActiveAssignmentsTable extends TableWidget
{
    protected static ?string $heading = 'Suivi des Équipages sur les Routes';

    protected static ?int $sort = 7;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                VehicleAssignment::where('status', AssignmentStatus::ACTIVE)
                    ->with(['vehicle', 'employee', 'chantier'])
            )
            ->columns([
                TextColumn::make('vehicle.reference')
                    ->label('Code Véhicule')
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->description(fn (VehicleAssignment $record) => $record->vehicle->license_plate),

                TextColumn::make('employee.full_name')
                    ->label('Conducteur Principal (Garde)')
                    ->icon(Phosphor::User),

                TextColumn::make('chantier.name')
                    ->label('Chantier de destination')
                    ->placeholder('Usage Logistique / Dépôt'),

                TextColumn::make('started_at')
                    ->label('Prise en main des clés')
                    ->dateTime('d/m/Y H:i')
                    ->color('primary'),

                TextColumn::make('passengers_count')
                    ->label('Co-voiturage')
                    ->getStateUsing(fn (VehicleAssignment $record) => $record->passengers()->count().' passager(s)')
                    ->badge()
                    ->color('info')
                    ->icon(Phosphor::UsersThree),
            ])
            ->recordActions([
                Action::make('open_assignment')
                    ->label('Consulter')
                    ->icon(Phosphor::Eye)
                    ->url(fn (VehicleAssignment $record) => "/flotte/vehicle-assignments/{$record->id}/edit"),
            ]);
    }
}
