<?php

namespace App\Observers\Chantiers;

use App\Jobs\Chantiers\RecalculateChantierProgressJob;
use App\Models\Chantiers\ChantierTask;

class ChantierTaskObserver
{
    public function saved(ChantierTask $task): void
    {
        // On récupère le chantier via la relation Task -> Phase -> Chantier
        $chantier = $task->phase->chantier;

        if ($chantier) {
            // Déclenche le recalcul asynchrone de la progression pondérée
            RecalculateChantierProgressJob::dispatch($chantier);
        }
    }

    public function deleted(ChantierTask $task): void
    {
        $chantier = $task->phase->chantier;

        if ($chantier) {
            RecalculateChantierProgressJob::dispatch($chantier);
        }
    }
}
