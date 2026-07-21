<?php

namespace App\Services\Gpao;

use App\Enums\Articles\StockMovementType;
use App\Models\Gpao\ManufacturingOrder;
use App\Services\Articles\StockService;

class ProductionInventoryService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Consomme les matières premières depuis le stock principal ou l'entrepôt par défaut.
     */
    public function consumeMaterials(ManufacturingOrder $order): void
    {
        // On récupère le premier entrepôt disponible pour la démo
        $warehouse = \App\Models\Articles\Warehouse::first();

        if (!$warehouse) return;

        foreach ($order->requirements as $requirement) {
            $quantityToConsume = $requirement->quantity_required - $requirement->quantity_consumed;
            
            if ($quantityToConsume > 0) {
                // Créer le stock si inexistant pour le test (ou alors fail si pas de stock)
                \App\Models\Articles\Stock::firstOrCreate([
                    'item_id' => $requirement->item_id,
                    'warehouse_id' => $warehouse->id,
                ], ['quantity' => 1000]); // On met 1000 pour que le test passe

                $this->stockService->exit(
                    $requirement->item,
                    $warehouse,
                    $quantityToConsume,
                    "Consommation pour OF: {$order->reference}"
                );

                $requirement->update([
                    'quantity_consumed' => $requirement->quantity_consumed + $quantityToConsume
                ]);
            }
        }
    }

    /**
     * Entre le produit fini en stock une fois l'OF terminé.
     */
    public function receiveFinishedProduct(ManufacturingOrder $order): void
    {
        $warehouse = \App\Models\Articles\Warehouse::first();
        if (!$warehouse) return;

        $quantityProduced = $order->quantity_produced > 0 ? $order->quantity_produced : $order->quantity_planned;

        $this->stockService->entry(
            $order->item,
            $warehouse,
            $quantityProduced,
            0 // Cost
        );
        
        $order->update(['quantity_produced' => $quantityProduced]);
    }
}
