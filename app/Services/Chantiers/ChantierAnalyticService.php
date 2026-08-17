<?php

namespace App\Services\Chantiers;

use App\Enums\Articles\StockMouvementSource;
use App\Enums\Commerce\OrderStatus;
use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Locations\InternalRentalInvoiceStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Articles\StockMouvement;
use App\Models\Chantiers\Chantier;
use App\Models\Chantiers\ChantierTask;
use App\Models\Commerce\PurchaseOrder;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Immobilisation\AssetMaintenance;
use App\Models\Immobilisation\Depreciation;
use App\Models\Locations\InternalRentalInvoice;
use App\Models\Locations\RentalContract;
use App\Models\RH\Employee;
use App\Models\RH\EquipementAssignment;
use App\Services\Locations\RentalCostService;

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

        // 2. Analyse Matériaux (via module Stocks)
        // On récupère les mouvements de sortie (OUT) affectés au chantier (SITE)
        $materialCost = StockMouvement::query()
            ->outgoing()
            ->bySource(StockMouvementSource::SITE)
            ->where('reference_id', $chantier->id)
            ->with('stock.item')
            ->get()
            ->sum(function ($mouvement) {
                $unitPrice = $mouvement->stock->item->purchase_price ?? 0;

                // quantity_delta est négatif pour une sortie (OUT), on inverse le signe pour le coût
                return abs($mouvement->quantity_delta) * $unitPrice;
            });

        $subcontractingCost = 0; // Sera lié au module Facturation Fournisseur

        // 3. Analyse des Véhicules (Module Flottes)
        $fleetAssignmentCost = VehicleAssignment::query()
            ->where('chantier_id', $chantier->id)
            ->whereIn('status', [
                AssignmentStatus::ACTIVE,
                AssignmentStatus::COMPLETED,
            ])
            ->with('vehicle')
            ->get()
            ->sum(fn ($assignment) => $assignment->getCost());

        $fuelCost = FuelTransaction::query()
            ->where('chantier_id', $chantier->id)
            ->sum('cost_ht');

        $fleetCost = $fleetAssignmentCost + $fuelCost;

        // 4. Avancement Technique Pondéré
        $progress = $this->calculateWeightedProgress($chantier);

        // 5. Amortissements des Immobilisations (Dotations imputées au chantier)
        $assetDepreciationCost = Depreciation::query()
            ->where('chantier_id', $chantier->id)
            ->where('is_passed', true)
            ->sum('amount');

        // 6. Réparations et Entretien des Immobilisations sur le chantier
        $assetMaintenanceCost = AssetMaintenance::query()
            ->where('chantier_id', $chantier->id)
            ->sum('cost_ht');

        // 7. Coûts de Location (Module Locations)
        $rentalCostService = app(RentalCostService::class);
        $rentalCost = RentalContract::query()
            ->where('chantier_id', $chantier->id)
            ->get()
            ->sum(fn ($contract) => $rentalCostService->getCumulativeCost($contract));

        // 8. Coûts d'immobilisation de l'Outillage/Gros Matériel (Module RH)
        $equipmentCost = EquipementAssignment::query()
            ->where('chantier_id', $chantier->id)
            ->with('equipement')
            ->get()
            ->sum(fn ($assignment) => $assignment->getImmobilizationCost());

        // 9. Achats directs (Commandes fournisseurs pour le chantier)
        $purchaseCost = PurchaseOrder::query()
            ->where('chantier_id', $chantier->id)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->sum('total_ht');

        // 10. Suivi bancaire (Transactions affectées au chantier)
        $bankIncome = (float) $chantier->bankTransactions()
            ->incomes()
            ->sum('amount');

        $bankExpense = (float) abs($chantier->bankTransactions()
            ->expenses()
            ->sum('amount'));

        $bankNet = $bankIncome - $bankExpense; // trésorerie nette (encaissements - décaissements)

        // 11. Facturation interne des immobilisations (refacturation au chantier)
        $internalRentalCost = (float) InternalRentalInvoice::query()
            ->where('chantier_id', $chantier->id)
            ->where('status', '!=', InternalRentalInvoiceStatus::CANCELED->value)
            ->sum('amount_ht');

        $totalCost = $laborCost + $materialCost + $subcontractingCost + $fleetCost + $assetDepreciationCost + $assetMaintenanceCost + $rentalCost + $equipmentCost + $purchaseCost + $internalRentalCost;
        $budget = (float) $chantier->budget_total_ht;
        $marginReal = $budget - $totalCost;

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
                'fleet_cost_real' => $fleetCost,
                'asset_depreciation_cost_real' => (float) $assetDepreciationCost,
                'asset_maintenance_cost_real' => (float) $assetMaintenanceCost,
                'rental_cost_real' => (float) $rentalCost,
                'equipment_cost_real' => (float) $equipmentCost,
                'purchase_cost_real' => (float) $purchaseCost,
                'internal_rental_cost_real' => $internalRentalCost,
                'total_cost_real' => $totalCost,
                'budget_ht' => $budget,
                'margin_real' => $marginReal,
                'margin_percent' => $budget > 0 ? ($marginReal / $budget) * 100 : 0,
                'bank_income_real' => $bankIncome,
                'bank_expense_real' => $bankExpense,
                'bank_net_real' => $bankNet,
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
