<?php

namespace App\Services\Gpao;

use App\Models\Gpao\ManufacturingOrder;
use App\Models\Gpao\ManufacturingRequirement;
use App\Models\Articles\ItemComposition;

class MrpService
{
    /**
     * Calcule et génère les besoins en composants pour un Ordre de Fabrication.
     */
    public function generateRequirementsForOrder(ManufacturingOrder $order): void
    {
        // On supprime les besoins existants si on recalcule
        $order->requirements()->delete();

        // On récupère les compositions (recette) de l'article à fabriquer
        $compositions = ItemComposition::where('parent_item_id', $order->item_id)->get();

        foreach ($compositions as $composition) {
            // Quantité requise = Quantité de l'OF * Quantité de la recette * (1 + % de perte)
            $lossMultiplier = 1 + ($composition->loss_percentage / 100);
            $quantityRequired = $order->quantity_planned * $composition->quantity * $lossMultiplier;

            ManufacturingRequirement::create([
                'manufacturing_order_id' => $order->id,
                'item_id' => $composition->child_item_id,
                'quantity_required' => $quantityRequired,
                'quantity_consumed' => 0,
            ]);
        }
    }
}
