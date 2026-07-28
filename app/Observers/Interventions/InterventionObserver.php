<?php

namespace App\Observers\Interventions;

use App\Models\Interventions\Intervention;

class InterventionObserver
{
    /**
     * Handle the Intervention "creating" event.
     */
    public function creating(Intervention $intervention): void
    {
        if (empty($intervention->company_id)) {
            // Assign a default company if none is provided
            $defaultCompany = \App\Models\Core\Company::first();
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
        //
    }

    /**
     * Handle the Intervention "updated" event.
     */
    public function updated(Intervention $intervention): void
    {
        // Si l'intervention passe en terminée et qu'elle est liée à un chantier, on pourrait imputer le budget
        // Toutefois, le budget est souvent imputé via les StockMovement et les Payrolls.
        // Ici, on pourrait ajouter une logique si nécessaire.
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
