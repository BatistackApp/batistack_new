<?php

namespace App\Observers\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Chantiers\RecalculateChantierProgressJob;
use App\Models\Flottes\VehicleAssignment;

class VehicleAssignmentObserver
{
    public function created(VehicleAssignment $assignment): void
    {
        if ($assignment->status === AssignmentStatus::ACTIVE) {
            $assignment->vehicle->updateQuietly([
                'status' => VehicleStatus::ASSIGNED,
            ]);
        }
    }

    public function updated(VehicleAssignment $assignment): void
    {
        // Si l'affectation vient d'être clôturée (passage de ACTIVE à COMPLETED)
        if ($assignment->wasChanged('status') && $assignment->status === AssignmentStatus::COMPLETED) {
            $vehicle = $assignment->vehicle;

            // 1. Mise à jour de l'odomètre réel du véhicule et libération du statut
            if ($assignment->end_odometer > $vehicle->odometer) {
                $vehicle->updateQuietly([
                    'odometer' => $assignment->end_odometer,
                    'status' => VehicleStatus::AVAILABLE,
                ]);
            } else {
                $vehicle->updateQuietly([
                    'status' => VehicleStatus::AVAILABLE,
                ]);
            }

            // 2. Déclenchement asynchrone du recalcul du coût analytique pour le Chantier
            if ($assignment->chantier_id) {
                // Dispatch d'un job de recalcul ou appel au service analytique du module chantiers
                // pour mettre à jour la variable C_LOG (Logistique) du chantier.
                RecalculateChantierProgressJob::dispatch($assignment->chantier);
            }
        }
    }
}
