<?php

namespace App\Services\Chantiers;

use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Services\Core\DocumentService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Service de Génération de Documents Chantiers (Impression).
 * S'appuie sur le DocumentService du Core.
 */
class ChantierDocumentService extends DocumentService
{
    public function __construct(protected ChantierAnalyticService $analyticService) {}

    /**
     * Génère la Fiche de Lancement de Chantier (Ordre de Service interne).
     */
    public function generateStartOrder(Chantier $chantier): string
    {
        $chantier->load(['client', 'manager', 'members']);

        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'title' => 'ORDRE DE SERVICE : '.$chantier->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'ganttTasks' => $this->getGanttTasks($chantier),
            'deployedResources' => $this->getDeployedResources($chantier),
            'position' => 'landscape',
        ];

        return $this->generate(
            'pdf.chantiers.start_order',
            $data,
            'os_'.$chantier->reference,
            'chantiers/orders'
        );
    }

    protected function getGanttTasks(Chantier $chantier): array
    {
        $tasks = [];
        $chantierStart = $chantier->start_date_preview ? $chantier->start_date_preview->format('Y-m-d') : now()->format('Y-m-d');
        $chantierEnd = $chantier->end_date_preview ? $chantier->end_date_preview->format('Y-m-d') : now()->addMonths(1)->format('Y-m-d');

        $tasks[] = [
            'id' => 'chantier_' . $chantier->id,
            'name' => $chantier->name,
            'start' => $chantierStart,
            'end' => $chantierEnd,
            'progress' => 0,
            'dependencies' => '',
            'custom_class' => 'bar-milestone',
        ];

        $phases = $chantier->phases()->with('tasks')->get();
        foreach ($phases as $phase) {
            $phaseId = 'phase_' . $phase->id;
            $phaseStart = $phase->start_date ? $phase->start_date->format('Y-m-d') : $chantierStart;
            $phaseEnd = $phase->end_date ? $phase->end_date->format('Y-m-d') : $chantierEnd;

            $tasks[] = [
                'id' => $phaseId,
                'name' => 'Phase: ' . $phase->label,
                'start' => $phaseStart,
                'end' => $phaseEnd,
                'progress' => 0,
                'dependencies' => 'chantier_' . $chantier->id,
                'custom_class' => 'bar-phase',
            ];

            $previousTaskId = null;
            foreach ($phase->tasks as $task) {
                $taskId = 'task_' . $task->id;
                $taskStart = $task->start_date ? $task->start_date->format('Y-m-d') : $phaseStart;
                $taskEnd = $task->end_date ? $task->end_date->format('Y-m-d') : $phaseEnd;

                $dependencies = $phaseId;
                if ($previousTaskId) {
                    $dependencies .= ', ' . $previousTaskId;
                }

                $tasks[] = [
                    'id' => $taskId,
                    'name' => $task->label,
                    'start' => $taskStart,
                    'end' => $taskEnd,
                    'progress' => $task->progress_percentage ?? 0,
                    'dependencies' => $dependencies,
                    'custom_class' => $task->is_completed ? 'bar-task-completed' : 'bar-task',
                ];
                $previousTaskId = $taskId;
            }
        }

        return $tasks;
    }

    protected function getDeployedResources(Chantier $chantier): array
    {
        $resources = [];

        // 1. Assets from Immobilisation
        foreach ($chantier->fixedAssets as $asset) {
            $resources[] = [
                'name' => $asset->name,
                'type' => 'Matériel Propre',
                'supplier' => '-',
                'status' => $asset->status?->getLabel() ?? 'Actif',
                'start_date' => $asset->purchase_date?->format('d/m/Y') ?? '-',
                'end_date' => '-',
            ];
        }

        // 2. Assets from Rental Contracts
        $chantier->loadMissing(['rentalContracts.lines', 'rentalContracts.supplier']);
        foreach ($chantier->rentalContracts as $contract) {
            foreach ($contract->lines as $line) {
                $resources[] = [
                    'name' => $line->name,
                    'type' => 'Location',
                    'supplier' => $contract->supplier?->name ?? '-',
                    'status' => $contract->status?->getLabel() ?? 'Actif',
                    'start_date' => $contract->start_date?->format('d/m/Y') ?? '-',
                    'end_date' => $contract->end_date?->format('d/m/Y') ?? ($contract->end_date_preview?->format('d/m/Y') ?? '-'),
                ];
            }
        }

        // 3. Vehicles
        $vehicleAssignments = \App\Models\Flottes\VehicleAssignment::with(['vehicle', 'employee'])
            ->where('chantier_id', $chantier->id)
            ->get();
            
        foreach ($vehicleAssignments as $assignment) {
            $resources[] = [
                'name' => $assignment->vehicle->brand . ' ' . $assignment->vehicle->model . ' (' . $assignment->vehicle->license_plate . ')',
                'type' => 'Véhicule',
                'supplier' => $assignment->employee ? $assignment->employee->full_name : 'Sans conducteur',
                'status' => $assignment->status?->getLabel() ?? 'Actif',
                'start_date' => $assignment->started_at?->format('d/m/Y') ?? '-',
                'end_date' => $assignment->ended_at?->format('d/m/Y') ?? '-',
            ];
        }

        return $resources;
    }

    /**
     * Génère le Procès-Verbal (PV) de Réception de Travaux.
     * Document contractuel majeur pour la levée des réserves.
     */
    public function generateHandoverProtocol(Chantier $chantier): string
    {
        $chantier->load(['client', 'manager']);

        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'title' => 'PV DE RÉCEPTION : '.$chantier->name,
            'generated_at' => now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.chantiers.handover_protocol',
            $data,
            'pv_reception_'.$chantier->reference,
            'chantiers/legal'
        );
    }

    /**
     * Génère le Rapport de Rentabilité Analytique.
     */
    public function generateRentabilityReport(Chantier $chantier): string
    {
        $metrics = $this->analyticService->getPerformanceMetrics($chantier);

        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'metrics' => $metrics,
            'title' => 'BILAN ANALYTIQUE : '.$chantier->name,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
            'position' => 'landscape',
        ];

        return $this->generate(
            'pdf.chantiers.rentability',
            $data,
            'bilan_'.$chantier->reference,
            'chantiers/reports'
        );
    }

    /**
     * Génère le Journal de Chantier Hebdomadaire.
     */
    public function generateWeeklyJournal(Chantier $chantier, Carbon|CarbonInterface $startDate): string
    {
        $endDate = $startDate->copy()->endOfWeek();
        $logs = $chantier->logs()->whereBetween('date', [$startDate, $endDate])->get();

        $data = [
            'company' => Company::first(),
            'chantier' => $chantier,
            'logs' => $logs,
            'period' => "Semaine du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}",
            'title' => 'JOURNAL DE CHANTIER : '.$chantier->reference,
            'generated_at' => Carbon::now()->format('d/m/Y H:i'),
        ];

        return $this->generate(
            'pdf.chantiers.journal',
            $data,
            'journal_'.$chantier->reference.'_'.$startDate->format('Y_W'),
            'chantiers/journals'
        );
    }
}
