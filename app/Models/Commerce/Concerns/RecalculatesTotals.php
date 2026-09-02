<?php

namespace App\Models\Commerce\Concerns;

trait RecalculatesTotals
{
    public function recalculateTotals(): void
    {
        $items = $this->items()->with('vatRate')->get();

        $totalHt = 0;
        $totalTva = 0;

        foreach ($items as $item) {
            $lineHt = (float) $item->quantity * (float) ($item->price_unit ?? $item->selling_price ?? 0);
            $rate = $item->vatRate ? (float) $item->vatRate->rate : 0;
            $totalHt += $lineHt;
            $totalTva += $lineHt * ($rate / 100);
        }

        $this->update([
            'total_ht' => round($totalHt, 2),
            'total_tva' => round($totalTva, 2),
            'total_ttc' => round($totalHt + $totalTva, 2),
        ]);
    }
}
