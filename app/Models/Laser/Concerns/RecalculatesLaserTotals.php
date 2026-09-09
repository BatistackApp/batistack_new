<?php

namespace App\Models\Laser\Concerns;

trait RecalculatesLaserTotals
{
    public function recalculateTotals(): void
    {
        $totalHt = (float) $this->lines()->sum('total_ht');
        $vatRate = (float) config('laser.vat_rate', 20);
        $totalTva = round($totalHt * ($vatRate / 100), 2);

        $this->update([
            'total_ht'  => round($totalHt, 2),
            'total_ttc' => round($totalHt + $totalTva, 2),
        ]);
    }
}
