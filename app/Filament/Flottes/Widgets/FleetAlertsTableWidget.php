<?php

namespace App\Filament\Flottes\Widgets;

use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use App\Services\Flottes\VehicleAlertService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class FleetAlertsTableWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Vigilance Échéances, Contrôles & Révisions';

    public function table(Table $table): Table
    {
        $alertService = app(VehicleAlertService::class);

        return $table
            ->query(
                Vehicle::query()
                    ->where(function ($q) {
                        $q->where('pollution_control_due_at', '<=', now()->addDays(45))
                            ->orWhere('status', VehicleStatus::BROKEN);
                    })
                    ->with(['maintenances', 'contracts'])
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Code Interne')
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->description(fn (Vehicle $record) => $record->brand.' '.$record->model),

                TextColumn::make('license_plate')
                    ->label('Plaque')
                    ->fontFamily('mono')
                    ->weight('semibold'),

                TextColumn::make('pollution_control_due_at')
                    ->label('Échéance Pollution (VUL)')
                    ->date('d/m/Y')
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : ($state && $state->diffInDays(now()) < 30 ? 'warning' : 'gray'))
                    ->placeholder('Non concerné'),

                TextColumn::make('maintenance_alert')
                    ->label('Statut Révision')
                    ->getStateUsing(function (Vehicle $record) use ($alertService) {
                        $limit = $record->usage_unit === 'hours' ? 250.00 : 20000.00;
                        $unit = $record->usage_unit === 'hours' ? 'h' : 'km';

                        return $alertService->needsMaintenance($record, $limit)
                            ? 'Révision requise ⚠️'
                            : 'À jour';
                    })
                    ->badge()
                    ->color(fn ($state) => $state === 'À jour' ? 'success' : 'warning'),

                TextColumn::make('status')
                    ->label('État opérationnel')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('Gérer l\'actif')
                    ->icon(Phosphor::ArrowRight)
                    ->url(fn (Vehicle $record) => "/flotte/vehicles/{$record->id}/edit"),
            ]);
    }
}
