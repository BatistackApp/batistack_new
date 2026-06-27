<?php

namespace App\Services\RH;

use App\Models\Chantiers\WeatherAlert;
use App\Models\RH\CibtpDeclaration;
use Carbon\Carbon;

class CibtpService
{
    /**
     * Generates a draft CIBTP declaration from a WeatherAlert.
     */
    public function generateDraftFromAlert(WeatherAlert $alert): ?CibtpDeclaration
    {
        // Don't create multiple declarations for the same alert
        if (CibtpDeclaration::where('weather_alert_id', $alert->id)->exists()) {
            return null;
        }

        $chantier = $alert->chantier;
        
        // Estimate the number of employees affected
        // In a real application, you might check Chantier->members() or specific schedules
        $affectedEmployeesCount = $chantier->members()->count() ?: 1; 

        // Assuming 7 hours lost per affected employee for a full day alert
        $estimatedLostHours = $affectedEmployeesCount * 7.0;

        return CibtpDeclaration::create([
            'chantier_id' => $chantier->id,
            'weather_alert_id' => $alert->id,
            'date' => $alert->started_at->toDateString(),
            'status' => 'draft',
            'total_lost_hours' => $estimatedLostHours,
        ]);
    }
}
