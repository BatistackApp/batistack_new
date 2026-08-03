<?php

namespace App\Filament\Interventions\Widgets;

use App\Models\Interventions\ClientEquipment;
use App\Services\Interventions\PredictiveMaintenanceService;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Commerce\CustomerQuote;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class PredictiveMaintenanceWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Équipements à risque de panne (Maintenance Prédictive)';

    public function table(Table $table): Table
    {
        $service = app(PredictiveMaintenanceService::class);
        $riskyData = $service->getEquipmentsAtRisk(30);
        $riskyIds = $riskyData->pluck('equipment.id')->toArray();

        return $table
            ->query(
                ClientEquipment::query()
                    ->whereIn('id', $riskyIds)
                    ->with('thirdParty')
            )
            ->columns([
                TextColumn::make('thirdParty.name')->label('Client')->searchable(),
                TextColumn::make('name')->label('Équipement')->searchable(),
                TextColumn::make('mtbf')->label('MTBF (Jours)')
                    ->getStateUsing(function (ClientEquipment $record) use ($riskyData) {
                        return $riskyData->firstWhere('equipment.id', $record->id)['mtbf_days'] ?? '-';
                    }),
                TextColumn::make('risk_score')->label('Risque de panne')
                    ->getStateUsing(function (ClientEquipment $record) use ($riskyData) {
                        $score = $riskyData->firstWhere('equipment.id', $record->id)['risk_score'] ?? 0;
                        return $score . '%';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        (int)$state >= 80 => 'danger',
                        (int)$state >= 50 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('next_failure')->label('Panne estimée')
                    ->getStateUsing(function (ClientEquipment $record) use ($riskyData) {
                        $date = $riskyData->firstWhere('equipment.id', $record->id)['predicted_date'] ?? null;
                        return $date ? $date->format('d/m/Y') : '-';
                    }),
            ])
            ->recordActions([
                Action::make('proposer_contrat')
                    ->label('Proposer contrat')
                    ->icon(Phosphor::FileText)
                    ->color('primary')
                    ->action(function (ClientEquipment $record, PredictiveMaintenanceService $service) {
                        $quote = $service->generateMaintenanceQuote($record);
                        Notification::make()
                            ->title('Devis généré avec succès')
                            ->success()
                            ->send();
                        // Optional: Redirect to quote edit page
                        // return redirect(CustomerQuoteResource::getUrl('edit', ['record' => $quote]));
                    })
            ]);
    }
}
