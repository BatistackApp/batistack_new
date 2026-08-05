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
            $inventoryService = app(InventoryService::class);

            foreach ($cycle->lines as $line) {
                if ($line->counted_quantity !== null && $line->counted_quantity != $line->theoretical_quantity) {
                    // Appliquer la régularisation
                    $inventoryService->reconcile(
                        $line->item,
                        $cycle->warehouse,
                        $line->counted_quantity,
                        "Régularisation inventaire tournant #{$cycle->id}"
                    );
                }
            }

            $cycle->update([
                'status' => InventoryCycleStatus::COMPLETED,
                'approved_by' => $manager->id,
                'approved_at' => now(),
            ]);
        });
    }
}
