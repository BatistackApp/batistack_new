<?php

namespace App\Observers\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Notifications\RH\TimeEntryStatusNotification;

class TimeEntryObserver
{
    public function updated(TimeEntry $timeEntry): void
    {
        // Si le statut passe à 'submitted', on notifie les validateurs
        if ($timeEntry->isDirty('status') && $timeEntry->status === TimeEntryStatus::SUBMITTED) {
            $this->notifyValidators($timeEntry);
        }

        // Si le statut passe à 'approved' ou repasse en 'draft' (refus), on notifie l'employé
        if ($timeEntry->isDirty('status') && in_array($timeEntry->status, [TimeEntryStatus::APPROVED, TimeEntryStatus::DRAFT])) {
            $timeEntry->employee->notify(new TimeEntryStatusNotification($timeEntry));
        }
    }

    /**
     * Notifie les conducteurs de travaux pour validation.
     */
    protected function notifyValidators(TimeEntry $timeEntry): void
    {
        $validators = User::admin()->get(); // À filtrer par rôle 'Conducteur de Travaux'

        Notification::send($validators, new TimeEntryStatusNotification($timeEntry));
    }
}
