<?php

namespace App\Observers\Flottes;

use App\Models\Flottes\TrafficFine;
use App\Notifications\Flottes\TrafficFineReceivedNotification;
use App\Services\Flottes\TrafficFineService;

class TrafficFineObserver
{
    public function creating(TrafficFine $fine): void
    {
        if (empty($fine->employee_id)) {
            // Utilisation du service de contravention pour chercher un conducteur actif à cette date/heure
            $resolvedDriverId = app(TrafficFineService::class)->resolveDriverForFine($fine);

            if ($resolvedDriverId) {
                $fine->employee_id = $resolvedDriverId;
            }
        }
    }

    public function created(TrafficFine $fine): void
    {
        if ($fine->employee) {
            // Notification automatique du salarié (RH) de la réception d'un PV à son nom
            $fine->employee->notify(new TrafficFineReceivedNotification($fine));
        }
    }
}
