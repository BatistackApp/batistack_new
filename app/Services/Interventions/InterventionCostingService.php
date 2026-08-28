<?php

namespace App\Services\Interventions;

use App\Enums\Interventions\InterventionType;
use App\Models\Interventions\Intervention;

class InterventionCostingService
{
    /**
     * Calcule le coût total (heures + matériel) pour l'entreprise
     */
    public function calculateTotalCost(Intervention $intervention): float
    {
        $laborCost = $intervention->workers->sum(function ($worker) {
            return $worker->hours_worked * $worker->hourly_cost;
        });

        $materialCost = $intervention->materials->sum(function ($material) {
            return $material->quantity * $material->unit_cost;
        });

        return $laborCost + $materialCost;
    }

    /**
     * Calcule le montant total facturable (HT)
     */
    public function calculateBillableAmount(Intervention $intervention): float
    {
        if ($intervention->type === InterventionType::FORFAIT) {
            return (float) $intervention->flat_rate_price;
        }

        // Si type Régie, facturation réelle (heures + matériels)
        // Dans une V2 on pourrait avoir un "selling_price" horaire sur InterventionWorker.
        // Pour l'instant, disons qu'on facture le matériel via son selling_price.
        // Les heures en régie peuvent avoir un taux de vente global ou par employé.

        $materialBilled = $intervention->materials->sum(function ($material) {
            return $material->quantity * $material->selling_price;
        });

        // Supposons que le hourly_cost stocké soit le prix coûtant. Il faudrait idéalement un hourly_billing_rate.
        // Faisons simple : si on est en régie, on suppose qu'une méthode ou règle détermine la marge.
        // Pour l'instant, on laisse les heures à 0 facturées si on n'a pas de selling_rate, ou on peut les ajouter si la db évolue.
        return $materialBilled;
    }

    /**
     * Calcule la rentabilité de l'intervention
     */
    public function calculateProfitability(Intervention $intervention): array
    {
        $cost = $this->calculateTotalCost($intervention);
        $revenue = $this->calculateBillableAmount($intervention);

        return [
            'cost' => $cost,
            'revenue' => $revenue,
            'margin' => $revenue - $cost,
            'margin_percent' => $revenue > 0 ? (($revenue - $cost) / $revenue) * 100 : 0,
        ];
    }
}
