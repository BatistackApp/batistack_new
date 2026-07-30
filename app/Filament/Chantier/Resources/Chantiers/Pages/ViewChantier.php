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

            \Filament\Actions\Action::make('generate_pv')
                ->label('PV de Réception')
                ->icon(\ToneGabes\Filament\Icons\Enums\Phosphor::Handshake)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Générer le Procès-Verbal de Réception')
                ->modalDescription('Le PV sera généré et une demande de signature sera automatiquement envoyée par email au client.')
                ->action(function (\App\Models\Chantiers\Chantier $record, \App\Services\Chantiers\ChantierDocumentService $service, \App\Services\Core\SignatureService $signatureService) {
                    $relativePath = $service->generateHandoverProtocol($record);
                    $disk = \App\Services\Core\DocumentService::getDisk();
                    
                    $client = $record->client;
                    $contact = $client?->getPrimaryContact();
                    $email = $contact?->email ?? $client?->email;
                    $name = $contact ? trim("{$contact->first_name} {$contact->last_name}") : ($client?->name ?? 'Client');
                    
                    if ($email) {
                        $signatureService->driver()->requestSignature(
                            model: $record,
                            type: \App\Enums\Core\SignatureType::AUTOGRAPH,
                            email: $email,
                            name: $name,
                            documentPath: $relativePath
                        );
                        
                        \Filament\Notifications\Notification::make()
                            ->title('PV de Réception généré')
                            ->body("Une demande de signature a été envoyée au client ({$email}).")
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('PV de Réception généré')
                            ->body("Le PV a été généré, mais le client n'a pas d'adresse email renseignée pour l'envoi de la signature.")
                            ->warning()
                            ->send();
                    }
                    
                    return response()->download(\Illuminate\Support\Facades\Storage::disk($disk)->path($relativePath));
                }),

            \Filament\Actions\Action::make('generate_doe')
                ->label('Générer le DOE')
                ->icon(\ToneGabes\Filament\Icons\Enums\Phosphor::Archive)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Générer le Dossier d\'Ouvrage Exécuté')
                ->modalDescription('Cette action va compiler tous les plans et fiches techniques validés en une seule archive ZIP.')
                ->action(function (\App\Models\Chantiers\Chantier $record, \App\Services\Chantiers\DoeDocumentService $service) {
                    try {
                        $path = $service->compileDoe($record);
                        return response()->download($path);
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Erreur lors de la génération du DOE')
                            ->body($e->getMessage())
                            ->send();
                    }
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
