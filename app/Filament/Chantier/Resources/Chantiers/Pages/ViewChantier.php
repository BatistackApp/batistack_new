<?php

namespace App\Filament\Chantier\Resources\Chantiers\Pages;

use App\Enums\Commerce\InvoiceStatus;
use App\Enums\Commerce\InvoiceType;
use App\Enums\Core\SignatureType;
use App\Enums\Flottes\AssignmentStatus;
use App\Enums\RH\QualificationType;
use App\Filament\Chantier\Resources\Chantiers\ChantierResource;
use App\Filament\Chantier\Resources\Chantiers\Widgets\ChantierFinancialOverview;
use App\Filament\Chantier\Resources\Chantiers\Widgets\ChantierGanttWidget;
use App\Filament\Chantier\Resources\Chantiers\Widgets\DeployedResourcesWidget;
use App\Filament\Chantier\Resources\Chantiers\Widgets\LaborDistributionChart;
use App\Filament\Chantier\Resources\Chantiers\Widgets\ReservesOverviewWidget;
use App\Models\Chantiers\Chantier;
use App\Models\Commerce\CustomerInvoice;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Services\Chantiers\ChantierAnalyticService;
use App\Services\Chantiers\ChantierDocumentService;
use App\Services\Chantiers\DoeDocumentService;
use App\Services\Core\DocumentService;
use App\Services\Core\SignatureService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use ToneGabes\Filament\Icons\Enums\Phosphor;

class ViewChantier extends ViewRecord
{
    protected static string $resource = ChantierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('print_start_order')
                    ->label('Ordre de Service')
                    ->icon(Phosphor::FilePdf)
                    ->action(function (Chantier $record, ChantierDocumentService $service) {
                        $path = $service->generateStartOrder($record);

                        return response()->download(Storage::disk(DocumentService::getDisk())->path($path));
                    }),
                Action::make('print_rentability')
                    ->label('Bilan Analytique')
                    ->icon(Phosphor::ChartLineUp)
                    ->action(function (Chantier $record, ChantierDocumentService $service) {
                        $path = $service->generateRentabilityReport($record);

                        return response()->download(Storage::disk(DocumentService::getDisk())->path($path));
                    }),
                Action::make('print_journal')
                    ->label('Journal de Chantier')
                    ->icon(Phosphor::BookOpen)
                    ->action(function (Chantier $record, ChantierDocumentService $service) {
                        $path = $service->generateWeeklyJournal($record, now());

                        return response()->download(Storage::disk(DocumentService::getDisk())->path($path));
                    }),
                Action::make('print_ppsps')
                    ->label('PPSPS (Sécurité)')
                    ->icon(Phosphor::HardHat)
                    ->color('danger')
                    ->action(function (Chantier $record, ChantierDocumentService $service) {
                        $path = $service->generatePpsps($record);

                        return response()->download(Storage::disk(DocumentService::getDisk())->path($path));
                    }),
            ])
                ->label('Impressions')
                ->icon(Phosphor::Printer)
                ->button()
                ->color('gray'),

            Action::make('generate_invoice')
                ->label('Facturer Situation')
                ->icon(Phosphor::Receipt)
                ->color('success')
                ->visible(fn (Chantier $record) => $record->quote_id !== null)
                ->schema([
                    TextInput::make('percentage')
                        ->label('Pourcentage d\'avancement à facturer')
                        ->numeric()
                        ->default(fn (Chantier $record, ChantierAnalyticService $service) => $service->getPerformanceMetrics($record)['progress'])
                        ->minValue(1)
                        ->maxValue(100)
                        ->required()
                        ->suffix('%'),
                ])
                ->action(function (Chantier $record, array $data) {
                    $quote = $record->quote;
                    if (! $quote) {
                        return;
                    }

                    $percentage = $data['percentage'] / 100;
                    $amountHt = $quote->total_ht * $percentage;
                    $amountTva = $quote->total_tva * $percentage;

                    $invoice = CustomerInvoice::create([
                        'client_id' => $record->client_id,
                        'chantier_id' => $record->id,
                        'reference' => 'FACT-SIT-'.uniqid(),
                        'type' => InvoiceType::SITUATION,
                        'status' => InvoiceStatus::DRAFT,
                        'total_ht' => $amountHt,
                        'total_tva' => $amountTva,
                        'total_ttc' => $amountHt + $amountTva,
                        'due_date' => now()->addDays(30),
                        'responsable_id' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Facture de situation créée (Brouillon)')
                        ->success()
                        ->send();
                }),

            Action::make('affect_vehicle')
                ->label('Assigner Véhicule')
                ->icon(Phosphor::Truck)
                ->color('info')
                ->schema([
                    Select::make('vehicle_id')
                        ->label('Véhicule / Engin')
                        ->options(Vehicle::all()->mapWithKeys(fn ($v) => [$v->id => "{$v->brand} {$v->model} ({$v->license_plate})"]))
                        ->required()
                        ->searchable(),
                    Select::make('employee_id')
                        ->label('Conducteur (Optionnel)')
                        ->options(function (ViewRecord $livewire) {
                            $options = [];
                            $chantier = $livewire->getRecord();
                            $employees = $chantier->members()->with(['currentContract', 'qualifications'])->get();
                            foreach ($employees as $emp) {
                                $job = $emp->currentContract?->job_title ?? 'Non défini';
                                $hasPermis = $emp->qualifications->contains(function ($q) {
                                    return $q->type === QualificationType::PERMIS && $q->isActive();
                                });
                                $status = $hasPermis ? '🚗 Permis OK' : '❌ Pas de permis';
                                $options[$emp->id] = "{$emp->full_name} | {$job} | {$status}";
                            }

                            return $options;
                        })
                        ->searchable(),
                    DatePicker::make('started_at')
                        ->label('Date de début')
                        ->default(now())
                        ->required(),
                    DatePicker::make('ended_at')
                        ->label('Date de fin (Prévue)'),
                ])
                ->action(function (Chantier $record, array $data) {
                    VehicleAssignment::create([
                        'vehicle_id' => $data['vehicle_id'],
                        'chantier_id' => $record->id,
                        'employee_id' => $data['employee_id'] ?? null,
                        'started_at' => $data['started_at'],
                        'ended_at' => $data['ended_at'] ?? null,
                        'status' => AssignmentStatus::ACTIVE,
                        'purpose' => 'Affectation Chantier '.$record->reference,
                        'start_odometer' => Vehicle::find($data['vehicle_id'])->odometer ?? 0,
                    ]);

                    Notification::make()
                        ->title('Véhicule assigné au chantier')
                        ->success()
                        ->send();
                }),

            Action::make('generate_pv')
                ->label('PV de Réception')
                ->icon(Phosphor::Handshake)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Générer le Procès-Verbal de Réception')
                ->modalDescription('Le PV sera généré et une demande de signature sera automatiquement envoyée par email au client.')
                ->action(function (Chantier $record, ChantierDocumentService $service, SignatureService $signatureService) {
                    $relativePath = $service->generateHandoverProtocol($record);
                    $disk = DocumentService::getDisk();

                    $client = $record->client;
                    $contact = $client?->getPrimaryContact();
                    $email = $contact?->email ?? $client?->email;
                    $name = $contact ? trim("{$contact->first_name} {$contact->last_name}") : ($client?->name ?? 'Client');

                    if ($email) {
                        $signatureService->driver()->requestSignature(
                            model: $record,
                            type: SignatureType::AUTOGRAPH,
                            email: $email,
                            name: $name,
                            documentPath: $relativePath
                        );

                        Notification::make()
                            ->title('PV de Réception généré')
                            ->body("Une demande de signature a été envoyée au client ({$email}).")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('PV de Réception généré')
                            ->body("Le PV a été généré, mais le client n'a pas d'adresse email renseignée pour l'envoi de la signature.")
                            ->warning()
                            ->send();
                    }

                    return response()->download(Storage::disk($disk)->path($relativePath));
                }),

            Action::make('generate_doe')
                ->label('Générer le DOE')
                ->icon(Phosphor::Archive)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Générer le Dossier d\'Ouvrage Exécuté')
                ->modalDescription('Cette action va compiler tous les plans et fiches techniques validés en une seule archive ZIP.')
                ->action(function (Chantier $record, DoeDocumentService $service) {
                    try {
                        $path = $service->compileDoe($record);

                        return response()->download($path);
                    } catch (\Exception $e) {
                        Notification::make()
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
            ChantierFinancialOverview::class,
            LaborDistributionChart::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            ChantierGanttWidget::class,
            DeployedResourcesWidget::class,
            ReservesOverviewWidget::class,
        ];
    }
}
