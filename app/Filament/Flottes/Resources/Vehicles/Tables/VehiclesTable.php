<?php

namespace App\Filament\Flottes\Resources\Vehicles\Tables;

use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use App\Models\Flottes\Vehicle;
use App\Services\Flottes\FleetDocumentService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('avatar')
                    ->label('')
                    ->collection('photos')
                    ->circular(),
                TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('license_plate')
                    ->label('Immatriculation')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),
                TextColumn::make('brand')
                    ->label('Marque & Modèle')
                    ->getStateUsing(fn (Vehicle $record) => "{$record->brand} {$record->model}")
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                TextColumn::make('crit_air_level')
                    ->label('Crit\'Air')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'E', '1' => 'success',
                        '2' => 'info',
                        '3' => 'warning',
                        '4', '5' => 'danger',
                        default => 'gray'
                    })
                    ->placeholder('N/A'),
                TextColumn::make('odometer')
                    ->label('Compteur')
                    ->numeric(decimalPlaces: 1)
                    ->suffix(fn (Vehicle $record) => $record->usage_unit === 'hours' ? ' h' : ' km')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge(),
                TextColumn::make('tco_cache')
                    ->label('Valeur TCO')
                    ->money('EUR')
                    ->color('gray')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(VehicleStatus::class),
                SelectFilter::make('type')
                    ->options(VehicleType::class),
                SelectFilter::make('crit_air_level')
                    ->label('Contrainte ZFE')
                    ->options([
                        'E' => 'Crit\'Air E',
                        '1' => 'Crit\'Air 1',
                        '2' => 'Crit\'Air 2',
                        '3' => 'Crit\'Air 3',
                        '4' => 'Crit\'Air 4',
                        '5' => 'Crit\'Air 5',
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('printFiche')
                        ->label('Imprimer Fiche TCO')
                        ->icon(Phosphor::Printer)
                        ->color('info')
                        ->action(function (Vehicle $record, FleetDocumentService $service) {
                            $path = $service->generateVehicleFiche($record);
                            Notification::make()
                                ->title('Fiche TCO compilée avec succès')
                                ->success()
                                ->send();

                            return response()->download($path);
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
