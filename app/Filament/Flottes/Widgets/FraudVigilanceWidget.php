<?php

namespace App\Filament\Flottes\Widgets;

use App\Models\Flottes\FuelTransaction;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class FraudVigilanceWidget extends TableWidget
{
    protected static ?string $heading = 'Vigilance & Audit Anti-Fraude';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                FuelTransaction::query()
                    ->where('is_suspicious', true)
                    ->with(['vehicle', 'employee'])
            )
            ->columns([
                TextColumn::make('purchased_at')
                    ->label('Date/Heure')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('vehicle.reference')
                    ->label('Véhicule')
                    ->weight('bold')
                    ->fontFamily('mono')
                    ->description(fn (FuelTransaction $record) => $record->vehicle->license_plate),

                TextColumn::make('employee.full_name')
                    ->label('Conducteur identifié')
                    ->placeholder('Aucun (Usage hors planning) ⚠️')
                    ->color(fn ($state) => $state ? 'gray' : 'danger'),

                TextColumn::make('suspicion_reason')
                    ->label('Motif d\'alerte')
                    ->wrap()
                    ->color('danger')
                    ->weight('semibold'),

                TextColumn::make('cost_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->alignEnd(),
            ])
            ->recordActions([
                Action::make('audit')
                    ->label('Vérifier')
                    ->icon(Phosphor::ShieldCheck)
                    ->color('warning')
                    ->url(fn (FuelTransaction $record) => "/flotte/vehicles/{$record->vehicle_id}/edit"),
            ]);
    }
}
