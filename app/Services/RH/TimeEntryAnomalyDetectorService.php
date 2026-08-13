<?php

namespace App\Services\RH;

use App\Models\RH\TimeEntry;
use App\Models\Flottes\VehicleAssignment;
use App\Enums\RH\TimeEntryType;
use Carbon\Carbon;

class TimeEntryAnomalyDetectorService
{
    /**
     * Détecte les anomalies de pointage pour une journée donnée.
     *
     * @param Carbon $date
     * @param int|float $toleranceHours
     * @return int Le nombre d'anomalies détectées.
     */
    public function detectForDate(Carbon $date, float $toleranceHours = 1.0): int
    {
        // On récupère les pointages normaux de la journée qui ne sont pas en atelier (sédentaires)
        // et qui n'ont pas déjà une anomalie non résolue pour éviter de recalculer inutilement.
        $entries = TimeEntry::whereDate('date', $date)
            ->where('type', TimeEntryType::NORMAL->value)
            ->where('is_workshop', false)
            ->where('is_anomaly', false)
            ->get();

        $anomaliesCount = 0;

        foreach ($entries as $entry) {
            // Calculer la durée totale d'utilisation de véhicules (conducteur ou passager) pour cette journée
            $assignments = VehicleAssignment::where(function ($query) use ($entry) {
                    $query->where('employee_id', $entry->employee_id)
                          ->orWhereHas('passengers', function ($q) use ($entry) {
                              $q->where('employees.id', $entry->employee_id);
                          });
                })
                ->whereDate('started_at', '<=', $date)
                ->where(function($query) use ($date) {
                     $query->whereNull('ended_at')
                           ->orWhereDate('ended_at', '>=', $date);
                })
                ->get();
                
            $totalVehicleDuration = 0;
            
            foreach ($assignments as $assignment) {
                // Calcule le chevauchement pour la journée spécifique
                $start = $assignment->started_at < $date->copy()->startOfDay() ? $date->copy()->startOfDay() : $assignment->started_at;
                $end = (!$assignment->ended_at || $assignment->ended_at > $date->copy()->endOfDay()) ? $date->copy()->endOfDay() : $assignment->ended_at;
                
                if ($end > $start) {
                    $totalVehicleDuration += $start->diffInMinutes($end) / 60;
                }
            }
            
            // Si les heures pointées dépassent significativement les heures d'utilisation de véhicule
            if (($entry->hours - $totalVehicleDuration) > $toleranceHours) {
                $entry->update([
                    'is_anomaly' => true,
                    'anomaly_reason' => "Heures pointées ({$entry->hours}h) largement supérieures au temps d'utilisation de véhicule (" . round($totalVehicleDuration, 1) . "h). Écart: " . round($entry->hours - $totalVehicleDuration, 1) . "h",
                    'anomaly_resolved_at' => null,
                    'anomaly_resolved_by_id' => null,
                ]);
                $anomaliesCount++;
            }
        }
        
        return $anomaliesCount;
    }
}
