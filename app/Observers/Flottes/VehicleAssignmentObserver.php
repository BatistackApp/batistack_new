<?php

namespace App\Observers\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Chantiers\RecalculateChantierProgressJob;
use App\Models\Flottes\VehicleAssignment;
use Log;

class VehicleAssignmentObserver
{
    /**
     * Mise à jour lors de création.
     */
    public function created(VehicleAssignment $assignment): void
    {
        if ($assignment->status === AssignmentStatus::ACTIVE) {
            $assignment->vehicle->updateQuietly([
                'status' => VehicleStatus::ASSIGNED,
            ]);

            Log::info('Véhicule assigné', [
                'vehicle_reference' => $assignment->vehicle->reference,
                'employee_name' => $assignment->employee->getFullName(),
            ]);
        }
    }

    /**
     * Marque le véhicule comme assigné.
     *
     * @throws \Exception
     */
    public function creating(VehicleAssignment $assignment): void
    {
        // Validations
        if ($assignment->ended_at && $assignment->ended_at <= $assignment->started_at) {
            throw new \Exception('La date de fin doit être après la date de début.');
        }

        Log::info('Affectation créée', [
            'vehicle_id' => $assignment->vehicle_id,
            'employee_id' => $assignment->employee_id,
            'chantier_id' => $assignment->chantier_id,
        ]);
    }

    /**
     * Gère la clôture d'affectation.
     *
     * @throws \Exception
     */
    public function updating(VehicleAssignment $assignment): void
    {
        // Validations avant mise à jour
        if ($assignment->isDirty('ended_at') && $assignment->ended_at) {
            if ($assignment->ended_at <= $assignment->started_at) {
                throw new \Exception('La date de fin doit être après la date de début.');
            }
        }

        if ($assignment->isDirty('end_odometer') && $assignment->end_odometer) {
            if ($assignment->end_odometer < $assignment->start_odometer) {
                throw new \Exception("L'odomètre final ne peut pas être inférieur à l'odomètre initial.");
            }
        }
    }

    /**
     * Clôture l'affectation et impute les coûts.
     */
    public function updated(VehicleAssignment $assignment): void
    {
        // Si l'affectation vient d'être clôturée
        if ($assignment->wasChanged('status') && in_array($assignment->status, [AssignmentStatus::COMPLETED, AssignmentStatus::CANCELLED])) {
            $vehicle = $assignment->vehicle;

            // Mise à jour de l'odomètre et libération du véhicule
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

            Log::info('Affectation clôturée', [
                'vehicle_reference' => $vehicle->reference,
                'distance' => $assignment->getDistance(),
                'cost' => $assignment->getCost(),
            ]);

            // Recalcul du chantier si applicable
            if ($assignment->chantier_id) {
                RecalculateChantierProgressJob::dispatch($assignment->chantier);

                Log::info('Imputation analytique chantier', [
                    'chantier_id' => $assignment->chantier_id,
                    'vehicle_reference' => $vehicle->reference,
                ]);
            }
        }

        // Log les changements de statut
        if ($assignment->wasChanged('status')) {
            Log::info('Statut affectation modifié', [
                'assignment_id' => $assignment->id,
                'old_status' => $assignment->getOriginal('status'),
                'new_status' => $assignment->status,
            ]);
        }
    }

    /**
     * Validation avant suppression.
     *
     * @throws \Exception
     */
    public function deleting(VehicleAssignment $assignment): void
    {
        if ($assignment->status === AssignmentStatus::ACTIVE) {
            throw new \Exception('Impossible de supprimer une affectation active.');
        }

        Log::warning('Affectation supprimée', [
            'assignment_id' => $assignment->id,
            'vehicle_id' => $assignment->vehicle_id,
        ]);
    }
}
