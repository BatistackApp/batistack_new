<?php

namespace App\Models\Laser\Concerns;

trait RecalculatesLaserTotals
{
    public function recalculateTotals(): void
    {
        $lines = $this->lines()->get();
        $totalHt = (float) $lines->sum('total_ht');
        $totalTva = round($totalHt * 0.20, 2);

        $this->update([
            'total_ht'  => round($totalHt, 2),
            'total_ttc' => round($totalHt + $totalTva, 2),
        ]);
    }
}
