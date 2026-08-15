<?php

namespace App\Services\Chantiers;

use App\Models\Articles\Item;
use App\Models\Chantiers\Chantier;
use App\Models\Core\Company;
use App\Services\Securite\ProductRiskService;

/**
 * Compile les données nécessaires à la génération d'un PPSPS
 * en croisant tâches, matériel alloué et fiches de sécurité des produits.
 */
class PpspsService
{
    public function __construct(
        protected ProductRiskService $productRiskService,
        protected ChantierAnalyticService $analyticService,
        protected ChantierComplianceService $complianceService,
    ) {}

    /**
     * Construit le tableau de données du document PPSPS.
     */
    public function build(Chantier $chantier): array
    {
        $chantier->loadMissing(['client', 'manager', 'members', 'subcontractors']);

        $products = $this->collectProducts($chantier);
        $globalRisks = $this->productRiskService->risksForItems($products);

        return [
            'company' => Company::first(),
            'chantier' => $chantier,
            'client' => $chantier->client,
            'manager' => $chantier->manager,
            'members' => $chantier->members->map(function ($member) {
                return [
                    'employee' => $member,
                    'medical' => $member->medicalVisits()->latest('visit_date')->first(),
                    'qualifications' => $member->qualifications,
                ];
            })->all(),
            'subcontractors' => $chantier->subcontractors,
            'compliance' => $this->complianceService->checkTeamCompliance($chantier),
            'phases' => $this->buildPhases($chantier),
            'materials' => $this->collectMaterials($chantier),
            'products' => $products,
            'risks' => $globalRisks,
            'epi' => $this->productRiskService->epiForRisks($globalRisks),
            'collective' => $this->productRiskService->collectiveForRisks($globalRisks),
            'progress' => (int) round($this->analyticService->getPerformanceMetrics($chantier)['progress'] ?? 0),
        ];
    }

    /**
     * Produits (matériels) utilisés sur le chantier :
     * stocks alloués + ressources référencées sur les tâches.
     *
     * @return Item[]
     */
    protected function collectProducts(Chantier $chantier): array
    {
        $items = collect();

        $chantier->stocks()->with('item')->get()->each(function ($stock) use ($items) {
            if ($stock->item) {
                $items->push($stock->item);
            }
        });

        $chantier->phases()->with(['tasks.allocations'])->get()->each(function ($phase) use ($items) {
            foreach ($phase->tasks as $task) {
                foreach ($task->allocations as $allocation) {
                    if ($allocation->allocatable_type === Item::class && $allocation->allocatable) {
                        $items->push($allocation->allocatable);
                    }
                }
            }
        });

        return $items->filter()->unique('id')->values()->all();
    }

    /**
     * Matériel alloué au chantier (via l'entrepôt virtuel).
     */
    protected function collectMaterials(Chantier $chantier): array
    {
        return $chantier->stocks()
            ->with('item')
            ->get()
            ->filter(fn ($stock) => $stock->item !== null)
            ->map(fn ($stock) => [
                'item' => $stock->item,
                'quantity' => $stock->quantity,
                'unit' => $stock->item?->unit?->name ?? '-',
            ])
            ->values()
            ->all();
    }

    /**
     * Analyse de risques par phase, déduite des produits utilisés.
     */
    protected function buildPhases(Chantier $chantier): array
    {
        $chantier->loadMissing(['phases.tasks.allocations']);

        return $chantier->phases->map(function ($phase) {
            $phaseProducts = collect();

            foreach ($phase->tasks as $task) {
                foreach ($task->allocations as $allocation) {
                    if ($allocation->allocatable_type === Item::class && $allocation->allocatable) {
                        $phaseProducts->push($allocation->allocatable);
                    }
                }
            }

            $phaseProducts = $phaseProducts->filter()->unique('id')->values()->all();

            return [
                'phase' => $phase,
                'tasks' => $phase->tasks,
                'products' => $phaseProducts,
                'risks' => $this->productRiskService->risksForItems($phaseProducts),
            ];
        })->all();
    }
}
