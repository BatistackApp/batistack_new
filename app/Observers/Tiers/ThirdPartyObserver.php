<?php

namespace App\Observers\Tiers;

use App\Enums\Tiers\ThirdPartyType;
use App\Jobs\Tiers\SynchronizeSirenJob;
use App\Jobs\Tiers\VerifyGloabVigilanceJob;
use App\Models\Tiers\ThirdParty;
use Illuminate\Support\Str;

class ThirdPartyObserver
{
    public function creating(ThirdParty $thirdParty): void
    {
        // Normalisation automatique avant enregistrement
        $thirdParty->name = Str::upper($thirdParty->name);
        $thirdParty->legal_name = $thirdParty->legal_name ? Str::upper($thirdParty->legal_name) : null;

        if ($thirdParty->type === ThirdPartyType::SUPPLIER || $thirdParty->type === ThirdPartyType::SUBCONTRACTOR) {
            $thirdParty->supplier_score = 100;
        }
        if (empty($thirdParty->compliant_status)) {
            $thirdParty->compliant_status = ['compliant' => true, 'issues' => []];
        }
    }

    public function created(ThirdParty $thirdParty): void
    {
        VerifyGloabVigilanceJob::dispatch();
    }

    public function updated(ThirdParty $thirdParty): void
    {
        if ($thirdParty->wasChanged('siret') && $thirdParty->siret) {
            SynchronizeSirenJob::dispatch($thirdParty);
        }
    }
}
