<?php

namespace App\Observers\Chantiers;

use App\Models\Chantiers\ChantierReserve;
use Illuminate\Support\Facades\DB;

class ChantierReserveObserver
{
    public function creating(ChantierReserve $reserve): void
    {
        if (empty($reserve->reference)) {
            $reserve->reference = DB::transaction(function () {
                $year = now()->year;

                $latest = ChantierReserve::whereYear('created_at', $year)
                    ->lockForUpdate()
                    ->orderByRaw('LENGTH(reference) DESC')
                    ->orderBy('reference', 'desc')
                    ->first();

                $sequenceNumber = 1;
                if ($latest && preg_match('/^RS-'.$year.'-(\d+)$/', $latest->reference, $m)) {
                    $sequenceNumber = (int) $m[1] + 1;
                }

                return 'RS-'.$year.'-'.str_pad((string) $sequenceNumber, 4, '0', STR_PAD_LEFT);
            });
        }
    }
}
