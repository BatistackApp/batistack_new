<?php

namespace App\Observers\Tiers;

use App\Jobs\Tiers\SynchronizeSirenJob;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Str;

class ThirdPartyObserver
{
    public function creating(ThirdParty $thirdParty): void
    {
        // Normalisation automatique avant enregistrement
        $thirdParty->name = Str::upper($thirdParty->name);
        $thirdParty->legal_name = $thirdParty->legal_name ? Str::upper($thirdParty->legal_name) : null;
    }

    public function updated(ThirdParty $thirdParty): void
    {
        if ($thirdParty->wasChanged('siret') && $thirdParty->siret) {
            SynchronizeSirenJob::dispatch($thirdParty);
        }
    }
}
