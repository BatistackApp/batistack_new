<?php

namespace App\Observers\RH;

use App\Models\RH\Qualification;
use App\Models\User;
use App\Notifications\RH\QualificationExpiringNotification;
use Illuminate\Support\Facades\Notification;

class QualificationObserver
{
    public function created(Qualification $qualification): void
    {
        // Si l'habilitation est déjà périmée à la saisie, on alerte immédiatement
        if ($qualification->expires_at && $qualification->expires_at->isPast()) {
            $this->notifyManagers($qualification, true);
        }
    }

    public function updated(Qualification $qualification): void
    {
        // Logique de traçabilité si nécessaire
    }

    /**
     * Envoie une notification aux gestionnaires RH.
     */
    protected function notifyManagers(Qualification $qualification, bool $isExpired = false): void
    {
        $users = User::admin()->get(); // À filtrer par rôle 'RH' ou 'Admin'

        Notification::send($users, new QualificationExpiringNotification($qualification, $isExpired));
    }
}
