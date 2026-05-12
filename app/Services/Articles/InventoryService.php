<?php

namespace App\Services\Articles;

use App\Models\Articles\Item;
use App\Models\Articles\Stock;
use App\Models\Articles\Warehouse;
use Illuminate\Support\Facades\DB;
use Throwable;

class InventoryService
{
    /**
     * Effectue une régularisation de stock (Inventaire).
     *
     * @param  float  $foundQuantity  La quantité réellement comptée.
     *
     * @throws Throwable
     */
    public function reconcile(Item $item, Warehouse $warehouse, float $foundQuantity, string $reason): void
    {
        DB::transaction(function () use ($item, $warehouse, $foundQuantity) {
            $stock = Stock::where('item_id', $item->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            $theoreticalQuantity = $stock ? $stock->quantity : 0;
            $adjustment = $foundQuantity - $theoreticalQuantity;

            if ($adjustment == 0) {
                return;
            }

            // Mise à jour du stock
            Stock::updateOrCreate(
                ['item_id' => $item->id, 'warehouse_id' => $warehouse->id],
                ['quantity' => $foundQuantity]
            );

            // Logique d'audit à implémenter pour tracer l'ajustement analytique
        });
    }
}
