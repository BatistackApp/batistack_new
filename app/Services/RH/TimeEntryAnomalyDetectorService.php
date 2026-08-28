<?php

namespace App\Services\RH;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\RH\TimeEntryType;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\TimeEntry;
use Carbon\Carbon;

class TimeEntryAnomalyDetectorService
{
    /**
     * Détecte les anomalies de pointage pour une journée donnée.
     *
     * @return int Le nombre d'anomalies détectées.
     */
    public function detectForDate(Carbon $date, float $toleranceHours = 1.0): int
    {
        if ($toleranceHours < 0) {
            return 0;
        }

        // On récupère les pointages normaux de la journée qui ne sont pas en atelier (sédentaires)
        $entries = TimeEntry::whereDate('date', $date)
            ->where('type', TimeEntryType::NORMAL->value)
            ->where('is_workshop', false)
            ->where('is_anomaly', false)
            ->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        // Charger tous les VehicleAssignment pertinents pour la date, exclure les annulés
        $allAssignments = VehicleAssignment::with('passengers')
            ->where('status', '!=', AssignmentStatus::CANCELLED->value)
            ->whereDate('started_at', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('ended_at')
                    ->orWhereDate('ended_at', '>=', $date);
            })
            ->get();

        // Grouper les intervalles par employé (conducteur + passagers)
        $assignmentsByEmployee = [];

        foreach ($allAssignments as $assignment) {
            $start = $assignment->started_at < $date->copy()->startOfDay() ? $date->copy()->startOfDay() : $assignment->started_at;
            $end = (! $assignment->ended_at || $assignment->ended_at > $date->copy()->endOfDay()) ? $date->copy()->endOfDay() : $assignment->ended_at;

            if ($end > $start) {
                $interval = ['start' => $start, 'end' => $end];

                // Conducteur
                $assignmentsByEmployee[$assignment->employee_id][] = $interval;

                // Passagers
                foreach ($assignment->passengers as $passenger) {
                    $assignmentsByEmployee[$passenger->id][] = $interval;
                }
            }
        }

        $anomaliesCount = 0;

        foreach ($entries as $entry) {
            $empId = $entry->employee_id;
            $intervals = $assignmentsByEmployee[$empId] ?? [];

            $totalVehicleDuration = 0;

            if (! empty($intervals)) {
                // Trier les intervalles par date de début
                usort($intervals, fn ($a, $b) => $a['start'] <=> $b['start']);

                $merged = [];
                $current = $intervals[0];

                for ($i = 1; $i < count($intervals); $i++) {
                    if ($intervals[$i]['start'] <= $current['end']) {
                        // Chevauchement ou contigu : on étend la fin
                        if ($intervals[$i]['end'] > $current['end']) {
                            $current['end'] = $intervals[$i]['end'];
                        }
                    } else {
                        $merged[] = $current;
                        $current = $intervals[$i];
                    }
                }
                $merged[] = $current;

                foreach ($merged as $m) {
                    $totalVehicleDuration += $m['start']->diffInMinutes($m['end']) / 60;
                }
            }

            // Si les heures pointées dépassent significativement les heures d'utilisation de véhicule
            if (($entry->hours - $totalVehicleDuration) > $toleranceHours) {
                $entry->update([
                    'is_anomaly' => true,
                    'anomaly_reason' => "Heures pointées ({$entry->hours}h) largement supérieures au temps d'utilisation de véhicule (".round($totalVehicleDuration, 1).'h). Écart: '.round($entry->hours - $totalVehicleDuration, 1).'h',
                    'anomaly_resolved_at' => null,
                    'anomaly_resolved_by_id' => null,
                ]);
                $anomaliesCount++;
            }
        }

        return $anomaliesCount;
    }
}
