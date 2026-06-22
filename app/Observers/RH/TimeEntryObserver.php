<?php

namespace App\Observers\RH;

use App\Enums\RH\TimeEntryStatus;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Notifications\RH\TimeEntryStatusNotification;
use Illuminate\Support\Facades\Notification;
use Log;

class TimeEntryObserver
{
    /**
     * @throws \Exception
     */
    public function creating(TimeEntry $entry): void
    {
        if (!$entry->employee_id) {
            throw new \Exception('Employee required');
        }
        if (!$entry->date) {
            throw new \Exception('Date required');
        }
        if ($entry->hours < 0) {
            throw new \Exception('Hours must be positive');
        }
    }

    public function created(TimeEntry $timeEntry): void
    {
        Log::info('TimeEntry created', ['id' => $timeEntry->id, 'employee_id' => $timeEntry->employee_id, 'hours' => $timeEntry->hours]);
    }

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

        if ($timeEntry->isDirty('hours')) {
            Log::info('TimeEntry hours updated', ['id' => $timeEntry->id, 'old_hours' => $timeEntry->getOriginal('hours'), 'new_hours' => $timeEntry->hours]);
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
