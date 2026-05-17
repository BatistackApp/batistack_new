<?php

namespace App\Services\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DB;
use Exception;
use Throwable;

class VehicleAssignmentService
{
    /**
     * Crée une nouvelle affectation de véhicule après vérification d'absence de conflit.
     *
     * * @throws Exception
     */
    public function createAssignment(
        Vehicle $vehicle,
        Employee $employee,
        ?Chantier $chantier,
        Carbon|CarbonInterface $startedAt,
        Carbon|CarbonInterface|null $endedAt,
        ?string $purpose = null
    ): VehicleAssignment {
        // 1. Vérification stricte des conflits d'agenda
        if ($this->hasConflict($vehicle->id, $employee->id, $startedAt, $endedAt)) {
            throw new Exception("Conflit d'affectation détecté : le véhicule ou le salarié est déjà mobilisé sur cette période.");
        }

        // 2. Vérification de la disponibilité du véhicule
        if ($vehicle->status === VehicleStatus::BROKEN) {
            throw new Exception('Le véhicule sélectionné est actuellement en panne ou accidenté.');
        }

        return DB::transaction(function () use ($vehicle, $employee, $chantier, $startedAt, $endedAt, $purpose) {
            // Création de l'affectation
            $assignment = VehicleAssignment::create([
                'vehicle_id' => $vehicle->id,
                'employee_id' => $employee->id,
                'chantier_id' => $chantier?->id,
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
                'start_odometer' => $vehicle->odometer,
                'status' => AssignmentStatus::ACTIVE,
                'purpose' => $purpose,
            ]);

            // Mise à jour du statut du véhicule
            $vehicle->update(['status' => VehicleStatus::ASSIGNED]);

            return $assignment;
        });
    }

    /**
     * Clôture une affectation en mettant à jour le compteur kilométrique et en libérant le véhicule.
     *
     * * @throws Exception|Throwable
     */
    public function endAssignment(
        VehicleAssignment $assignment,
        Carbon|CarbonInterface $endedAt,
        float $endOdometer
    ): void {
        if ($assignment->status !== AssignmentStatus::ACTIVE) {
            throw new Exception("Cette affectation n'est plus active.");
        }

        if ($endOdometer < $assignment->start_odometer) {
            throw new Exception("Le kilométrage de retour ({$endOdometer} km) ne peut pas être inférieur au kilométrage de départ ({$assignment->start_odometer} km).");
        }

        DB::transaction(function () use ($assignment, $endedAt, $endOdometer) {
            // 1. Mise à jour de l'affectation
            $assignment->update([
                'ended_at' => $endedAt,
                'end_odometer' => $endOdometer,
                'status' => AssignmentStatus::COMPLETED,
            ]);

            // 2. Mise à jour du véhicule (odomètre et disponibilité)
            $assignment->vehicle->update([
                'odometer' => $endOdometer,
                'status' => VehicleStatus::AVAILABLE,
            ]);

            // 3. Imputation analytique optionnelle vers le module Chantiers
            if ($assignment->chantier_id) {
                $this->imputeAnalyticCostToChantier($assignment);
            }
        });
    }

    /**
     * Détecte les chevauchements temporels pour un véhicule ou pour un salarié.
     * Applique la formule mathématique : max(Start1, Start2) < min(End1, End2)
     */
    public function hasConflict(
        int $vehicleId,
        int $employeeId,
        Carbon|CarbonInterface $startAt,
        Carbon|CarbonInterface|null $endAt,
        ?int $ignoreAssignmentId = null
    ): bool {
        $query = VehicleAssignment::query()
            ->where('status', AssignmentStatus::ACTIVE)
            ->where(function ($q) use ($vehicleId, $employeeId) {
                $q->where('vehicle_id', $vehicleId)
                    ->orWhere('employee_id', $employeeId);
            });

        if ($ignoreAssignmentId) {
            $query->where('id', '!=', $ignoreAssignmentId);
        }

        if ($endAt) {
            $query->where(function ($q) use ($startAt, $endAt) {
                $q->whereBetween('started_at', [$startAt, $endAt])
                    ->orWhereBetween('ended_at', [$startAt, $endAt])
                    ->orWhere(function ($sub) use ($startAt, $endAt) {
                        $sub->where('started_at', '<=', $startAt)
                            ->where('ended_at', '>=', $endAt);
                    });
            });
        } else {
            // Affectation ouverte/continue : tout chevauchement en cours bloque
            $query->where(function ($q) use ($startAt) {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $startAt);
            });
        }

        return $query->exists();
    }

    /**
     * Calcule et notifie le coût d'affectation analytique d'un trajet vers un chantier.
     */
    protected function imputeAnalyticCostToChantier(VehicleAssignment $assignment): void
    {
        $distance = $assignment->end_odometer - $assignment->start_odometer;
        $cost = $distance * (float) $assignment->vehicle->km_rate;

        // Trace dans les logs applicatifs
        logger()->info("Imputation analytique : Le véhicule {$assignment->vehicle->reference} a généré un coût de {$cost} € HT pour le chantier {$assignment->chantier->reference} (Distance : {$distance} km).");

        // C_LOG est enrichi lors de l'appel du recalcul global du chantier
    }
}
