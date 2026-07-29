<?php

namespace App\Filament\Chantier\Resources\Chantiers\Pages;

use App\Filament\Chantier\Resources\Chantiers\ChantierResource;
use App\Filament\Chantier\Resources\Chantiers\Widgets\LaborDistributionChart;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewChantier extends ViewRecord
{
    protected static string $resource = ChantierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('generate_invoice')
                ->label('Facturer Situation')
                ->icon(\ToneGabes\Filament\Icons\Enums\Phosphor::Receipt)
                ->color('success')
                ->visible(fn (\App\Models\Chantiers\Chantier $record) => $record->quote_id !== null)
                ->schema([
                    \Filament\Forms\Components\TextInput::make('percentage')
                        ->label('Pourcentage d\'avancement à facturer')
                        ->numeric()
                        ->default(fn (\App\Models\Chantiers\Chantier $record, \App\Services\Chantiers\ChantierAnalyticService $service) => $service->getPerformanceMetrics($record)['progress'])
                        ->minValue(1)
                        ->maxValue(100)
                        ->required()
                        ->suffix('%'),
                ])
                ->action(function (\App\Models\Chantiers\Chantier $record, array $data) {
                    $quote = $record->quote;
                    if (!$quote) return;

                    $percentage = $data['percentage'] / 100;
                    $amountHt = $quote->total_ht * $percentage;
                    $amountTva = $quote->total_tva * $percentage;

                    $invoice = \App\Models\Commerce\CustomerInvoice::create([
                        'client_id' => $record->client_id,
                        'chantier_id' => $record->id,
                        'reference' => 'FACT-SIT-' . uniqid(),
                        'type' => \App\Enums\Commerce\InvoiceType::SITUATION,
                        'status' => \App\Enums\Commerce\InvoiceStatus::DRAFT,
                        'total_ht' => $amountHt,
                        'total_tva' => $amountTva,
                        'total_ttc' => $amountHt + $amountTva,
                        'due_date' => now()->addDays(30),
                        'responsable_id' => auth()->id(),
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Facture de situation créée (Brouillon)')
                        ->success()
                        ->send();
                }),

            \Filament\Actions\Action::make('affect_vehicle')
                ->label('Assigner Véhicule')
                ->icon(\ToneGabes\Filament\Icons\Enums\Phosphor::Truck)
                ->color('info')
                ->schema([
                    \Filament\Forms\Components\Select::make('vehicle_id')
                        ->label('Véhicule / Engin')
                        ->options(\App\Models\Flottes\Vehicle::all()->mapWithKeys(fn($v) => [$v->id => "{$v->brand} {$v->model} ({$v->license_plate})"]))
                        ->required()
                        ->searchable(),
                    \Filament\Forms\Components\Select::make('employee_id')
                        ->label('Conducteur (Optionnel)')
                        ->options(\App\Models\RH\Employee::all()->mapWithKeys(fn($e) => [$e->id => "{$e->first_name} {$e->last_name}"]))
                        ->searchable(),
                    \Filament\Forms\Components\DatePicker::make('started_at')
                        ->label('Date de début')
                        ->default(now())
                        ->required(),
                    \Filament\Forms\Components\DatePicker::make('ended_at')
                        ->label('Date de fin (Prévue)'),
                ])
                ->action(function (\App\Models\Chantiers\Chantier $record, array $data) {
                    \App\Models\Flottes\VehicleAssignment::create([
                        'vehicle_id' => $data['vehicle_id'],
                        'chantier_id' => $record->id,
                        'employee_id' => $data['employee_id'] ?? null,
                        'started_at' => $data['started_at'],
                        'ended_at' => $data['ended_at'] ?? null,
                        'status' => \App\Enums\Flottes\AssignmentStatus::ACTIVE,
                        'purpose' => 'Affectation Chantier ' . $record->reference,
                        'start_odometer' => \App\Models\Flottes\Vehicle::find($data['vehicle_id'])->odometer ?? 0,
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Véhicule assigné au chantier')
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Chantier\Resources\Chantiers\Widgets\ChantierFinancialOverview::class,
            LaborDistributionChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Chantier\Resources\Chantiers\Widgets\ChantierGanttWidget::class,
            \App\Filament\Chantier\Resources\Chantiers\Widgets\DeployedResourcesWidget::class,
        ];
    }
}
