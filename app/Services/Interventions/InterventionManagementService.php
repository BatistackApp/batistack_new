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

    public function completeIntervention(Intervention $intervention): bool
    {
        $updated = $intervention->update([
            'status' => InterventionStatus::TERMINEE,
            'completed_at' => now(),
        ]);

        if ($updated) {
            // Trigger stock decrement
            app(InterventionStockService::class)->processMaterials($intervention);
        }
        
        return $updated;
    }
}
