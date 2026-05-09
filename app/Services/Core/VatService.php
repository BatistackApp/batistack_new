<?php

namespace App\Services\Core;

use App\Models\Core\VatRate;
use Illuminate\Support\Facades\Cache;

class VatService
{
    /**
     * Calcule le montant TTC à partir d'un montant HT et d'un ID de taux de TVA.
     */
    public function calculateTotal(float $netAmount, int $vatRateId): float
    {
        $vatRate = Cache::remember("core_vat_rate_{$vatRateId}", 3600, function () use ($vatRateId) {
            return VatRate::find($vatRateId);
        });

        if (! $vatRate) {
            return $netAmount;
        }

        // Utilisation de la précision à 4 décimales pour le calcul intermédiaire
        $taxAmount = $netAmount * ($vatRate->rate / 100);

        return round($netAmount + $taxAmount, 2);
    }

    /**
     * Récupère le taux par défaut.
     */
    public function getDefaultVatRate(): ?VatRate
    {
        return VatRate::where('is_default', true)->first();
    }
}
