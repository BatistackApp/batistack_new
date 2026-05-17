<?php

namespace App\Observers\Chantiers;

use App\Models\Chantiers\ChantierLog;
use App\Notifications\Chantiers\ChantierIncidentNotification;

class ChantierLogObserver
{
    public function saved(ChantierLog $log): void
    {
        // Si un incident est signalé, on notifie immédiatement le responsable
        if ($log->incident_reported && ($log->wasRecentlyCreated || $log->wasChanged('incident_reported'))) {
            $manager = $log->chantier->manager;

            if ($manager) {
                $manager->notify(new ChantierIncidentNotification($log));
            }
        }
    }
}
