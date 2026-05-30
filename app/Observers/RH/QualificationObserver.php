<?php

namespace App\Observers\RH;

use App\Models\RH\Qualification;
use App\Models\User;
use App\Notifications\RH\QualificationExpiringNotification;
use Illuminate\Support\Facades\Notification;
use Log;

class QualificationObserver
{
    /**
     * @throws \Exception
     */
    public function creating(Qualification $qualification): void
    {
        if (empty($qualification->label)) {
            throw new \Exception('Qualification label required');
        }
        if (! $qualification->employee_id) {
            throw new \Exception('Employee required');
        }
    }

    public function created(Qualification $qualification): void
    {
        Log::info('Qualification created', ['id' => $qualification->id, 'label' => $qualification->label, 'employee_id' => $qualification->employee_id]);

        // Si l'habilitation est déjà périmée à la saisie, on alerte immédiatement
        if ($qualification->expires_at && $qualification->expires_at->isPast()) {
            $this->notifyManagers($qualification, true);
        }
    }

    public function updated(Qualification $qualification): void
    {
        if ($qualification->isDirty('expiration_date')) {
            Log::info('Qualification expiration date changed', ['id' => $qualification->id]);
        }
    }

    /**
     * Envoie une notification aux gestionnaires RH.
     */
    protected function notifyManagers(Qualification $qualification, bool $isExpired = false): void
    {
        $users = User::where('is_admin', true)->get();

        Notification::send($users, new QualificationExpiringNotification($qualification, $isExpired));
    }
}
