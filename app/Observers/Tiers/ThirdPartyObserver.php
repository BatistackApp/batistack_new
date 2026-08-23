<?php

namespace App\Observers\Tiers;

use App\Enums\Tiers\ThirdPartyDocumentStatus;
use App\Enums\Tiers\ThirdPartyType;
use App\Jobs\Tiers\CollectLegalDocumentsJob;
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

        if ($thirdParty->siren && in_array($thirdParty->type, [ThirdPartyType::SUBCONTRACTOR, ThirdPartyType::CLIENT])) {
            CollectLegalDocumentsJob::dispatch($thirdParty);
        }
    }

    public function updated(ThirdParty $thirdParty): void
    {
        if ($thirdParty->wasChanged('siret') && $thirdParty->siret) {
            SynchronizeSirenJob::dispatch($thirdParty);
        }

        if ($thirdParty->wasChanged('siren') && $thirdParty->siren
            && in_array($thirdParty->type, [ThirdPartyType::SUBCONTRACTOR, ThirdPartyType::CLIENT])) {
            $thirdParty->documents()->update(['status' => ThirdPartyDocumentStatus::EXPIRED]);
            CollectLegalDocumentsJob::dispatch($thirdParty);
        }
    }
}
