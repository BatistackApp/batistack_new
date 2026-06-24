<?php

namespace App\Observers\RH;

use App\Models\RH\MedicalVisit;
use App\Models\User;
use App\Notifications\RH\MedicalVisitReminderNotification;
use Illuminate\Support\Facades\Notification;
use Log;

class MedicalVisitObserver
{
    /**
     * @throws \Exception
     */
    public function creating(MedicalVisit $visit): void
    {
        if (! $visit->employee_id) {
            throw new \Exception('Employee required');
        }
        if (! $visit->visit_date) {
            throw new \Exception('Visit date required');
        }
        if (empty($visit->type)) {
            throw new \Exception('Visit type required');
        }
    }

    public function created(MedicalVisit $visit): void
    {
        Log::info('MedicalVisit created', ['id' => $visit->id, 'employee_id' => $visit->employee_id, 'type' => $visit->type->value, 'aptitude' => $visit->aptitude?->value]);

        // Si la visite est déjà expirée à la saisie, on alerte immédiatement
        if ($visit->isExpired()) {
            $this->notifyManagers($visit);
        }
    }

    public function updated(MedicalVisit $visit): void
    {
        if ($visit->isDirty('next_due_date')) {
            Log::info('MedicalVisit due date changed', ['id' => $visit->id, 'new_due_date' => $visit->next_due_date]);

            if ($visit->isExpiringsSoon(30)) {
                $this->notifyManagers($visit);
            }
        }

        if ($visit->isDirty('aptitude')) {
            Log::info('MedicalVisit aptitude changed', ['id' => $visit->id, 'old_aptitude' => $visit->getOriginal('aptitude'), 'new_aptitude' => $visit->aptitude?->value]);
        }
    }

    protected function notifyManagers(MedicalVisit $visit): void
    {
        $users = User::where('is_admin', true)->get();

        Notification::send($users, new MedicalVisitReminderNotification($visit));
    }
}
