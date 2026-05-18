<?php

namespace App\Services\Flottes;

use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use Illuminate\Database\Eloquent\Collection;

class VehicleAlertService
{
    /**
     * Identifie les contrats (assurance, leasing) arrivant à échéance sous N jours.
     */
    public function getExpiringContracts(int $days = 30): Collection
    {
        return VehicleContract::where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>', now())
            ->with('vehicle')
            ->get();
    }

    /**
     * Vérifie si un véhicule à dépasser son échéance de révision kilométrique.
     * Exemple : Révision préconisée tous les 20 000 km.
     */
    public function needsMaintenance(Vehicle $vehicle, ?float $interval = null): bool
    {
        if ($interval === null) {
            // RÈGLE MÉTIER BTP : Résolution automatique du seuil d'entretien par défaut
            $interval = $vehicle->usage_unit === 'hours' ? 250.00 : 20000.00;
        }

        $lastMaintenance = $vehicle->maintenances()
            ->whereNotNull('odometer_at_maintenance')
            ->latest('performed_at')
            ->first();

        $lastOdometer = $lastMaintenance ? (float) $lastMaintenance->odometer_at_maintenance : 0.0;
        $currentOdometer = (float) $vehicle->odometer;

        return ($currentOdometer - $lastOdometer) >= $interval;
    }
}
