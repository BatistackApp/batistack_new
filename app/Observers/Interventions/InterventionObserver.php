<?php

namespace App\Observers\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Core\Company;
use App\Models\Interventions\Intervention;
use App\Notifications\Customer\InterventionPlanifieeNotification;
use App\Notifications\Customer\InterventionTermineeNotification;

class InterventionObserver
{
    /**
     * Handle the Intervention "creating" event.
     */
    public function creating(Intervention $intervention): void
    {
        if (empty($intervention->company_id)) {
            // Assign a default company if none is provided
            $defaultCompany = Company::first();
            $intervention->company_id = $defaultCompany ? $defaultCompany->id : 1;
        }

        if (empty($intervention->reference)) {
            // Logique de génération (ex: INT-2026-0001)
            $year = now()->format('Y');
            $lastIntervention = Intervention::where('company_id', $intervention->company_id)
                ->whereYear('created_at', $year)
                ->latest('id')
                ->first();

            $sequence = $lastIntervention ? ((int) substr($lastIntervention->reference, -4)) + 1 : 1;
            $intervention->reference = sprintf('INT-%s-%04d', $year, $sequence);
        }
    }

    /**
     * Handle the Intervention "created" event.
     */
    public function created(Intervention $intervention): void
    {
        if ($intervention->status === InterventionStatus::PLANIFIEE && $intervention->thirdParty) {
            $contact = $intervention->thirdParty->primaryContact;
            if ($contact) {
                $contact->notify(new InterventionPlanifieeNotification($intervention));
            }
        }
    }

    /**
     * Handle the Intervention "updated" event.
     */
    public function updated(Intervention $intervention): void
    {
        if (! $intervention->isDirty('status') || ! $intervention->thirdParty) {
            return;
        }

        $contact = $intervention->thirdParty->primaryContact;
        if (! $contact) {
            return;
        }

        match ($intervention->status) {
            InterventionStatus::PLANIFIEE => $contact->notify(new InterventionPlanifieeNotification($intervention)),
            InterventionStatus::TERMINEE => $contact->notify(new InterventionTermineeNotification($intervention)),
            default => null,
        };
    }

    /**
     * Handle the Intervention "deleted" event.
     */
    public function deleted(Intervention $intervention): void
    {
        //
    }

    /**
     * Handle the Intervention "restored" event.
     */
    public function restored(Intervention $intervention): void
    {
        //
    }

    /**
     * Handle the Intervention "force deleted" event.
     */
    public function forceDeleted(Intervention $intervention): void
    {
        //
    }
}
