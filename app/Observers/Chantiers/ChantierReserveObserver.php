<?php

namespace App\Observers\Chantiers;

use App\Models\Chantiers\ChantierReserve;

class ChantierReserveObserver
{
    public function creating(ChantierReserve $reserve): void
    {
        if (empty($reserve->reference)) {
            $count = ChantierReserve::whereYear('created_at', now()->year)->count() + 1;
            $reserve->reference = 'RS-'.now()->year.'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        }
    }
}
