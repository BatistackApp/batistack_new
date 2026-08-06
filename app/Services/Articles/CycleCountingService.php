<?php

namespace App\Services\Articles;

use App\Enums\Articles\InventoryCycleLineStatus;
use App\Enums\Articles\InventoryCycleStatus;
use App\Enums\Articles\StockMouvementSource;
use App\Models\Articles\InventoryCycle;
use App\Models\Articles\InventoryCycleLine;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class CycleCountingService
{
    /**
     * Génère un nouveau cycle d'inventaire aléatoire pour un entrepôt donné.
     *
     * @param Warehouse $warehouse
     * @param int $itemCount Le nombre d'articles à compter
     * @param User|null $user L'utilisateur qui déclenche la génération (null = système)
     * @return InventoryCycle
     */
    public function generateCycle(Warehouse $warehouse, int $itemCount = 10, ?User $user = null): InventoryCycle
    {
        return DB::transaction(function () use ($warehouse, $itemCount, $user) {
            $cycle = InventoryCycle::create([
                'warehouse_id' => $warehouse->id,
                'name' => 'Inventaire Tournant - ' . $warehouse->name . ' - ' . now()->format('d/m/Y'),
                'status' => InventoryCycleStatus::PENDING,
                'created_by' => $user?->id,
            ]);

            // Sélection aléatoire d'articles en stock dans cet entrepôt
            // Idéalement, on pourrait filtrer par ceux n'ayant pas eu d'inventaire récent.
            $stocks = Stock::where('warehouse_id', $warehouse->id)
                ->inRandomOrder()
                ->limit($itemCount)
                ->get();

            foreach ($stocks as $stock) {
                InventoryCycleLine::create([
                    'inventory_cycle_id' => $cycle->id,
                    'item_id' => $stock->item_id,
                    'theoretical_quantity' => $stock->quantity,
                    'status' => InventoryCycleLineStatus::PENDING,
                ]);
            }

            return $cycle;
        });
    }

    /**
     * Soumet le cycle pour validation par un manager.
     */
    public function submitForReview(InventoryCycle $cycle): void
    {
        if ($cycle->status !== InventoryCycleStatus::PENDING) {
            throw new \App\Exceptions\Articles\ArticlesModuleException("Le cycle n'est pas dans un état soumettable.", 400);
        }

        if ($cycle->lines()->count() === 0) {
            throw new \App\Exceptions\Articles\ArticlesModuleException("Le cycle ne contient aucune ligne.", 400);
        }

        if ($cycle->lines()->whereNull('counted_quantity')->exists()) {
            throw new \App\Exceptions\Articles\ArticlesModuleException("Toutes les lignes doivent être comptées avant soumission.", 400);
        }

        $cycle->update([
            'status' => InventoryCycleStatus::PENDING_REVIEW,
        ]);
    }

    /**
     * Approuve le cycle et applique les régularisations de stock.
     *
     * @throws Throwable
     */
    public function approveCycle(InventoryCycle $cycle, User $manager): void
    {
        DB::transaction(function () use ($cycle, $manager) {
            $lockedCycle = InventoryCycle::with('lines.item')->lockForUpdate()->find($cycle->id);

            if ($lockedCycle->status !== InventoryCycleStatus::PENDING_REVIEW) {
                throw new \App\Exceptions\Articles\ArticlesModuleException("Ce cycle n'est plus en attente de validation.", 400);
            }

            foreach ($lockedCycle->lines as $line) {
                if ($line->counted_quantity === null || $line->status !== InventoryCycleLineStatus::COUNTED) {
                    throw new \App\Exceptions\Articles\ArticlesModuleException("Toutes les lignes doivent être comptées.", 400);
                }
            }

            $inventoryService = app(InventoryService::class);

            foreach ($lockedCycle->lines as $line) {
                if ($line->counted_quantity != $line->theoretical_quantity) {
                    // Appliquer la régularisation
                    $inventoryService->reconcile(
                        $line->item,
                        $lockedCycle->warehouse,
                        $line->counted_quantity,
                        "Régularisation inventaire tournant #{$lockedCycle->id}"
                    );
                }
            }

            $lockedCycle->update([
                'status' => InventoryCycleStatus::COMPLETED,
                'approved_by' => $manager->id,
                'approved_at' => now(),
            ]);
        });
    }
}
