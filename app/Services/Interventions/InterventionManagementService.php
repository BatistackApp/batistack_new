<?php

namespace App\Services\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;

class InterventionManagementService
{
    public function scheduleIntervention(Intervention $intervention, array $employeeIds): void
    {
        // Link workers to intervention
        foreach ($employeeIds as $employeeId) {
            $intervention->workers()->firstOrCreate([
                'employee_id' => $employeeId,
            ]);
        }

        $intervention->update([
            'status' => InterventionStatus::PLANIFIEE,
        ]);
        
        // Notification logic will be triggered via observers or controllers
    }

    public function startIntervention(Intervention $intervention): void
    {
        $intervention->update([
            'status' => InterventionStatus::EN_COURS,
        ]);
    }

    public function completeIntervention(Intervention $intervention): void
    {
        $intervention->update([
            'status' => InterventionStatus::TERMINEE,
            'completed_at' => now(),
        ]);

        // Trigger stock decrement
        app(InterventionStockService::class)->processMaterials($intervention);
        
        // Trigger billing if needed
        // This could be automatic or manual depending on business rules. We'll leave it manual/triggered elsewhere for now.
    }
}
