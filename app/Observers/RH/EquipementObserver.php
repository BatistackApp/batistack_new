<?php

namespace App\Observers\RH;

use App\Models\RH\Equipement;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Log;

class EquipementObserver
{
    /**
     * @throws \Exception
     */
    public function creating(Equipement $equipement): void
    {
        if (! $equipement->employee_id) {
            throw new \Exception('Employee required');
        }
        if (empty($equipement->label)) {
            throw new \Exception('Label required');
        }
        if (empty($equipement->serial_number)) {
            throw new \Exception('Serial number required');
        }
    }

    public function created(Equipement $equipement): void
    {
        Log::info('Equipement created', ['id' => $equipement->id, 'label' => $equipement->label, 'employee_id' => $equipement->employee_id]);

        // Si l'équipement est déjà expiré à la saisie, on alerte immédiatement
        if ($equipement->isExpired()) {
            $this->notifyManagers($equipement, true);
        }
    }

    public function updated(Equipement $equipement): void
    {
        if ($equipement->isDirty('expires_at')) {
            Log::info('Equipement expiration date changed', ['id' => $equipement->id, 'new_expiration' => $equipement->expires_at]);

            if ($equipement->isExpiringsSoon(30)) {
                $this->notifyManagers($equipement, false);
            }
        }

        if ($equipement->isDirty('last_check_at')) {
            Log::info('Equipement checked', ['id' => $equipement->id, 'checked_at' => $equipement->last_check_at]);
        }
    }

    /**
     * @throws \Exception
     */
    public function deleting(Equipement $equipement): void
    {
        if ($equipement->status === 'active') {
            throw new \Exception('Cannot delete active equipement');
        }
    }

    protected function notifyManagers(Equipement $equipement, bool $isExpired = false): void
    {
        $users = User::where('is_admin', true)->get();

        Notification::send($users, new EquipementExpiringNotification($equipement, $isExpired));
    }
}
