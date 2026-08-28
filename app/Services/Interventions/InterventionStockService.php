<?php

namespace App\Services\Interventions;

use App\Models\Articles\Stock;
use App\Models\Articles\StockMouvement;
use App\Models\Interventions\Intervention;

class InterventionStockService
{
    /**
     * Traite la consommation de matériel suite à la clôture de l'intervention.
     */
    public function processMaterials(Intervention $intervention): void
    {
        foreach ($intervention->materials as $material) {
            if ($material->warehouse_id) {
                // Fetch the actual stock
                $stock = Stock::where('item_id', $material->item_id)
                    ->where('warehouse_id', $material->warehouse_id)
                    ->first();

                $stockId = $stock ? $stock->id : 1;
                $quantityBefore = $stock ? $stock->quantity : 0;
                $quantityAfter = $quantityBefore - $material->quantity;

                StockMouvement::create([
                    'stock_id' => $stockId,
                    'user_id' => 1, // System or current user
                    'quantity_delta' => -$material->quantity, // Mouvement sortant
                    'quantity_before' => $quantityBefore,
                    'quantity_after' => $quantityAfter,
                    'type' => 'out',
                    'description' => "Déstockage pour intervention {$intervention->reference}",
                    'reference_type' => 'intervention',
                    'reference_id' => $intervention->id,
                ]);
            }
        }
    }
}
