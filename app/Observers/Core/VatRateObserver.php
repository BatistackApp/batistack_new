<?php

namespace App\Observers\Core;

use App\Models\Core\VatRate;
use Illuminate\Support\Facades\Cache;

class VatRateObserver
{
    public function saved(VatRate $vatRate): void
    {
        Cache::forget("core_vat_rate_{$vatRate->id}");
    }
}
