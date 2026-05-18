<?php

namespace App\Filament\Flottes\Resources\TrafficFines\Tables;

use App\Enums\Flottes\FineStatus;
use App\Models\Flottes\TrafficFine;
use App\Services\Flottes\TrafficFineService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class TrafficFinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('infraction_at', 'desc')
            ->columns([
                TextColumn::make('reference')
                    ->label('N° Avis / PV')
                    ->searchable()
                    ->fontFamily('mono')
                    ->sortable(),
                TextColumn::make('vehicle.license_plate')
                    ->label('Véhicule')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('employee.full_name')
                    ->label('Conducteur responsable')
                    ->placeholder('Non identifié ⚠️')
                    ->color(fn ($state) => $state ? 'gray' : 'danger')
                    ->searchable(),
                TextColumn::make('infraction_at')
                    ->label('Date d\'infraction')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Montant')
                    ->money('EUR')
                    ->color('danger')
                    ->weight('bold'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(FineStatus::class),
                SelectFilter::make('vehicle_id')
                    ->label('Filtrer par véhicule')
                    ->relationship('vehicle', 'license_plate'),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),

                    // --- ACTION MÉTIER : RELANCER LA RÉSOLUTION AUTOMATIQUE ---
                    Action::make('resolveDriver')
                        ->label('Identifier le chauffeur')
                        ->icon(Phosphor::UserFocus)
                        ->color('info')
                        ->visible(fn (TrafficFine $record) => empty($record->employee_id))
                        ->action(function (TrafficFine $record) {
                            $driverId = app(TrafficFineService::class)->resolveDriverForFine($record);

                            if ($driverId) {
                                $record->update(['employee_id' => $driverId]);
                                Notification::make()
                                    ->title('Conducteur résolu')
                                    ->success()
                                    ->body("L'infraction a été attribuée au conducteur actif à cette date.")
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Résolution impossible')
                                    ->warning()
                                    ->body("Aucune affectation de trajet active n'a été trouvée pour ce véhicule à cette heure-là.")
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
