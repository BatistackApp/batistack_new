<?php

namespace App\Services\Laser;

use App\Models\Laser\LaserQuote;
use App\Models\Laser\LaserQuoteLine;
use App\Support\ReferenceGenerator;

class LaserQuoteService
{
    public function generateReference(): string
    {
        return ReferenceGenerator::next('LAQ');
    }

    public function calculateLine(LaserQuoteLine $line): void
    {
        $line->recalculate();
    }

    public function applyDiscount(int $quantity): float
    {
        if ($quantity >= 20) {
            return 15.0;
        }
        if ($quantity >= 10) {
            return 10.0;
        }
        if ($quantity >= 5) {
            return 5.0;
        }

        return 0.0;
    }

    public function calculateLineTotal(
        float $weightKg,
        float $pricePerKg,
        float $cutLengthMm,
        float $pricePerMeter,
        float $programmingCost,
        int $quantity,
        float $discountPct,
    ): float {
        $prixPoids = $weightKg * $pricePerKg;
        $prixMetre = ($cutLengthMm / 1000) * $pricePerMeter;
        $unitPrice = max($prixPoids, $prixMetre) + $programmingCost;

        return $unitPrice * $quantity * (1 - $discountPct / 100);
    }
}
