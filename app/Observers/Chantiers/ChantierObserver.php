<?php

namespace App\Observers\Chantiers;

use App\Jobs\Chantiers\GeocodeChantierAddressJob;
use App\Jobs\Chantiers\InitializeChantierPhasesJob;
use App\Models\Chantiers\Chantier;
use Illuminate\Support\Str;

class ChantierObserver
{
    public function creating(Chantier $chantier): void
    {
        if (empty($chantier->uuid)) {
            $chantier->uuid = (string) Str::uuid();
        }
    }

    public function created(Chantier $chantier): void
    {
        InitializeChantierPhasesJob::dispatch($chantier);
        GeocodeChantierAddressJob::dispatch($chantier);
    }

    public function saved(Chantier $chantier): void
    {
        // On vérifie si l'un des composants de l'adresse a changé ou si c'est une création
        if ($chantier->wasRecentlyCreated || $chantier->wasChanged(['address', 'zip_code', 'city'])) {
            // Le job est dispatché vers la file d'attente pour ne pas ralentir l'UI
            GeocodeChantierAddressJob::dispatch($chantier);
        }
    }
}
