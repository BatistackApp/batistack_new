<?php

namespace App\Services\Chantiers;

use App\Enums\RH\TimeEntryStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierTask;
use App\Models\RH\Employee;

/**
 * Service Analytique et Financier des Chantiers.
 * Gère les calculs de rentabilité et de consommation de ressources.
 */
class ChantierAnalyticService
{
    /**
     * Calcule la rentabilité en temps réel (Prévu vs Réel).
     */
    /**
     * Calcule la rentabilité et les KPI en temps réel.
     */
    public function getPerformanceMetrics(Chantier $chantier): array
    {
        // 1. Analyse Main d'œuvre (MO)
        $realHours = $chantier->timeEntries()
            ->where('status', TimeEntryStatus::APPROVED)
            ->sum('hours');

        $laborCost = $chantier->timeEntries()
            ->where('status', TimeEntryStatus::APPROVED)
            ->with('employee.currentContract')
            ->get()
            ->sum(fn ($entry) => $entry->hours * ($entry->employee->currentContract?->hourly_rate ?? 0));

        // 2. Analyse Matériaux et Sous-traitance (Simulée en attendant les modules Achats)
        $materialCost = 0; // Sera lié au module Stocks/Achats
        $subcontractingCost = 0; // Sera lié au module Facturation Fournisseur

        // 3. Avancement Technique Pondéré
        $progress = $this->calculateWeightedProgress($chantier);

        return [
            'hours' => [
                'budget' => (float) $chantier->budget_hours,
                'real' => (float) $realHours,
                'percent' => $chantier->budget_hours > 0 ? ($realHours / $chantier->budget_hours) * 100 : 0,
            ],
            'financials' => [
                'labor_cost_real' => $laborCost,
                'material_cost_real' => $materialCost,
                'subcontracting_cost_real' => $subcontractingCost,
                'total_cost_real' => $laborCost + $materialCost + $subcontractingCost,
                'budget_ht' => (float) $chantier->budget_total_ht,
            ],
            'progress' => $progress,
        ];
    }

    /**
     * Vérifie si un employé est apte (sécurité) à être assigné au chantier.
     */
    public function canAssignEmployee(Chantier $chantier, Employee $employee): array
    {
        $employee->load(['medicalVisits', 'qualifications']);

        $errors = [];
        $isCompliant = true;

        // Vérification visite médicale
        $lastVisit = $employee->medicalVisits()->latest('visit_date')->first();
        if (! $lastVisit || $lastVisit->isExpired() || $lastVisit->aptitude->value === 'unfit') {
            $errors[] = 'Aptitude médicale expirée ou invalide.';
            $isCompliant = false;
        }

        // Ici on pourrait ajouter des vérifications spécifiques au type de chantier
        // ex: Si chantier électrique, vérifier habilitation B1V/H1V

        return [
            'status' => $isCompliant,
            'messages' => $errors,
        ];
    }

    /**
     * Calcule l'avancement basé sur le poids (heures estimées) de chaque tâche.
     */
    protected function calculateWeightedProgress(Chantier $chantier): float
    {
        $tasks = ChantierTask::whereIn('chantier_phase_id', $chantier->phases()->pluck('id'))->get();

        $totalEstimated = $tasks->sum('estimated_hours');
        if ($totalEstimated <= 0) {
            return 0;
        }

        $completedWeighted = $tasks->sum(fn ($t) => ($t->progress_percentage / 100) * $t->estimated_hours);

        return round(($completedWeighted / $totalEstimated) * 100, 2);
    }
}
