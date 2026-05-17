<?php

namespace App\Observers\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Flottes\RecalculateVehicleTcoJob;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleMaintenance;
use App\Notifications\Flottes\MaintenanceScheduledNotification;

class VehicleMaintenanceObserver
{
    public function created(VehicleMaintenance $maintenance): void
    {
        $vehicle = $maintenance->vehicle;

        // 1. Détection de la garde active du véhicule
        // On cherche le salarié qui utilise actuellement le véhicule concerné
        $activeAssignment = VehicleAssignment::query()
            ->where('vehicle_id', $maintenance->vehicle_id)
            ->where('status', AssignmentStatus::ACTIVE)
            ->where(function ($query) use ($maintenance) {
                // L'affectation doit couvrir la date prévue de la maintenance
                $query->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $maintenance->performed_at);
            })
            ->with('employee')
            ->first();

        // 2. Si un compagnon est identifié comme ayant la garde active, on l'alerte
        if ($activeAssignment && $activeAssignment->employee) {
            $activeAssignment->employee->notify(new MaintenanceScheduledNotification($maintenance));
        }

        // 3. Si l'intervention est planifiée aujourd'hui et immobilise le véhicule
        if ($maintenance->performed_at->isToday() && $this->isImmobilizing($maintenance->type)) {
            $vehicle->updateQuietly([
                'status' => VehicleStatus::MAINTENANCE,
            ]);
        }

        // 4. Lancement asynchrone du calcul du TCO global de l'actif
        RecalculateVehicleTcoJob::dispatch($vehicle);
    }

    public function saved(VehicleMaintenance $maintenance): void
    {
        $vehicle = $maintenance->vehicle;

        // 1. Si la maintenance s'est faite à un kilométrage supérieur, on met à jour le véhicule
        if ($maintenance->odometer_at_maintenance && $maintenance->odometer_at_maintenance > $vehicle->odometer) {
            $vehicle->updateQuietly([
                'odometer' => $maintenance->odometer_at_maintenance,
            ]);
        }

        // 2. Si le type de maintenance indique une immobilisation, on adapte le statut
        if (in_array(strtolower($maintenance->type), ['panne', 'accident', 'grosse réparation'])) {
            $vehicle->update([
                'status' => VehicleStatus::BROKEN,
            ]);
        }

        // 3. Lancement d'un Job asynchrone pour recalculer le TCO (Total Cost of Ownership)
        RecalculateVehicleTcoJob::dispatch($vehicle);
    }

    /**
     * Détermine si le type d'intervention nécessite une immobilisation immédiate du véhicule.
     */
    protected function isImmobilizing(string $type): bool
    {
        $type = strtolower($type);
        return str_contains($type, 'panne') ||
            str_contains($type, 'accident') ||
            str_contains($type, 'grosse réparation') ||
            str_contains($type, 'carrosserie');
    }
}
