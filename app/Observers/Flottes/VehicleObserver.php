<?php

namespace App\Observers\Flottes;

use App\Models\Flottes\Vehicle;
use Illuminate\Support\Str;
use Log;

class VehicleObserver
{
    /**
     * Génère la référence et normalise la plaque.
     */
    public function creating(Vehicle $vehicle): void
    {
        if (empty($vehicle->reference)) {
            $latestId = Vehicle::max('id') ?? 0;
            $vehicle->reference = 'VEH-'.str_pad($latestId + 1, 3, '0', STR_PAD_LEFT);
        }

        if ($vehicle->license_plate) {
            $vehicle->license_plate = Str::upper(str_replace([' ', '-'], '', $vehicle->license_plate));
        }

        Log::info('Véhicule créé', [
            'reference' => $vehicle->reference,
            'license_plate' => $vehicle->license_plate,
            'type' => $vehicle->type,
        ]);
    }

    /**
     * Normalise la plaque lors de mise à jour.
     */
    public function updating(Vehicle $vehicle): void
    {
        if ($vehicle->isDirty('license_plate')) {
            $vehicle->license_plate = Str::upper(str_replace([' ', '-'], '', $vehicle->license_plate));
        }

        // Log les changements importants
        if ($vehicle->isDirty('status')) {
            Log::info('Statut véhicule modifié', [
                'reference' => $vehicle->reference,
                'old_status' => $vehicle->getOriginal('status'),
                'new_status' => $vehicle->status,
            ]);
        }

        if ($vehicle->isDirty('odometer')) {
            Log::info('Odomètre mis à jour', [
                'reference' => $vehicle->reference,
                'old_odometer' => $vehicle->getOriginal('odometer'),
                'new_odometer' => $vehicle->odometer,
            ]);
        }
    }

    /**
     * Logging à la mise à jour.
     */
    public function updated(Vehicle $vehicle): void
    {
        if ($vehicle->wasChanged(['status', 'odometer', 'purchase_date', 'purchase_price'])) {
            Log::info('Véhicule mis à jour', [
                'reference' => $vehicle->reference,
                'changes' => $vehicle->getChanges(),
            ]);
        }
    }

    /**
     * Validations avant suppression.
     * @throws \Exception
     */
    public function deleting(Vehicle $vehicle): void
    {
        // Vérifier qu'il n'y a pas d'affectations actives
        if ($vehicle->assignments()->where('status', 'active')->exists()) {
            throw new \Exception("Impossible de supprimer {$vehicle->reference}: affectations actives détectées.");
        }

        Log::warning('Véhicule supprimé', [
            'reference' => $vehicle->reference,
            'license_plate' => $vehicle->license_plate,
        ]);
    }

    /**
     * Logging à la suppression.
     */
    public function deleted(Vehicle $vehicle): void
    {
        Log::info('Véhicule archivé', [
            'reference' => $vehicle->reference,
            'total_assignments' => $vehicle->assignments()->count(),
            'total_maintenance_cost' => $vehicle->maintenances()->sum('cost_ht'),
        ]);
    }
}
