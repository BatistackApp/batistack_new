<?php

namespace App\Services\Flottes;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use App\Enums\RH\MedicalAptitude;
use App\Enums\RH\QualificationType;
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
     * Crée une nouvelle affectation de véhicule après contrôles stricts de sécurité.
     *
     * @throws Exception
     */
    public function createAssignment(
        Vehicle $vehicle,
        Employee $employee,
        ?Chantier $chantier,
        Carbon|CarbonInterface $startedAt,
        Carbon|CarbonInterface|null $endedAt,
        ?string $purpose = null
    ): VehicleAssignment {

        // 1. Contrôle réglementaire et sécuritaire du conducteur (Sécurité BTP)
        $this->validateDriverCompliance($employee, $vehicle);

        // 2. Vérification d'absence de conflit d'agenda
        if ($this->hasConflict($vehicle->id, $employee->id, $startedAt, $endedAt)) {
            throw new Exception("Conflit d'affectation détecté : le véhicule ou le salarié est déjà mobilisé sur cette période.");
        }

        // 3. Vérification de la disponibilité mécanique du véhicule
        if ($vehicle->status === VehicleStatus::BROKEN) {
            throw new Exception('Le véhicule sélectionné est actuellement en panne ou accidenté.');
        }

        return DB::transaction(function () use ($vehicle, $employee, $chantier, $startedAt, $endedAt, $purpose) {
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

            $vehicle->update(['status' => VehicleStatus::ASSIGNED]);

            return $assignment;
        });
    }

    /**
     * Clôture une affectation et déclenche l'imputation de coût d'usure complet.
     *
     * @throws Exception|Throwable
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

        // Suppression du bloc DB::transaction interne
        $assignment->update([
            'ended_at' => $endedAt,
            'end_odometer' => $endOdometer,
            'status' => AssignmentStatus::COMPLETED,
        ]);

        $assignment->vehicle->update([
            'odometer' => $endOdometer,
            'status' => VehicleStatus::AVAILABLE,
        ]);

        if ($assignment->chantier_id) {
            $this->imputeAnalyticCostToChantier($assignment);
        }
    }

    /**
     * Valide l'aptitude médicale et légale de l'employé pour conduire l'actif sélectionné.
     *
     * @throws Exception
     */
    protected function validateDriverCompliance(Employee $employee, Vehicle $vehicle): void
    {
        // A. Vérification de l'Aptitude Médicale (VIP / SIR)
        $lastVisit = $employee->medicalVisits()->latest('visit_date')->first();
        if (! $lastVisit || $lastVisit->isExpired() || $lastVisit->aptitude === MedicalAptitude::UNFIT) {
            throw new Exception("Aptitude médicale invalide ou expirée pour le collaborateur : {$employee->full_name}. Affectation impossible.");
        }

        // B. Vérification des Habilitations Techniques (CACES ou Permis B)
        if ($vehicle->type === VehicleType::SPECIAL) {
            // Les engins spéciaux nécessitent une attestation CACES valide
            $hasCaces = $employee->qualifications()
                ->where('expires_at', '>', now())
                ->where('type', QualificationType::CACES)
                ->exists();

            if (! $hasCaces) {
                throw new Exception("Le collaborateur {$employee->full_name} ne possède pas de certificat CACES valide pour conduire cet engin spécial.");
            }
        } else {
            // Les véhicules routiers standards (VUL, commerciaux) exigent un permis de conduire valide
            $hasLicense = $employee->qualifications()
                ->where('expires_at', '>', now())
                ->where('type', QualificationType::PERMIS)
                ->exists();

            if (! $hasLicense) {
                throw new Exception("Aucun permis de conduire valide enregistré dans le dossier RH de {$employee->full_name}. Affectation impossible.");
            }
        }
    }

    /**
     * Calcule et impute le coût réel (Usure marginale + Quote-part d'amortissement TCO) au chantier.
     */
    protected function imputeAnalyticCostToChantier(VehicleAssignment $assignment): void
    {
        $distance = $assignment->end_odometer - $assignment->start_odometer;
        if ($distance <= 0) {
            return;
        }

        $vehicle = $assignment->vehicle;
        $kmRate = (float) $vehicle->km_rate;
        $totalOdometer = (float) $vehicle->odometer;
        $tco = (float) ($vehicle->tco_cache ?? 0);

        // Amortissement de base : TCO divisé par le kilométrage parcouru globalement par l'actif
        $amortizationRate = $totalOdometer > 0 ? ($tco / $totalOdometer) : 0;

        // Formule : Coût complet = Distance * (km_rate + (TCO / Odomètre total))
        $totalImputedCost = $distance * ($kmRate + $amortizationRate);

        // Traçabilité analytique dans les logs Batistack
        logger()->info("Imputation analytique : Le véhicule {$vehicle->reference} a généré un coût de ".round($totalImputedCost, 2)." € HT pour le chantier #{$assignment->chantier_id} (Kilomètres parcourus : {$distance} km, dont quote-part d'amortissement : ".round($distance * $amortizationRate, 2).' € HT).');
    }

    /**
     * Détecte les chevauchements temporels.
     */
    public function hasConflict(int $vehicleId, int $employeeId, Carbon|CarbonInterface $startAt, Carbon|CarbonInterface|null $endAt, ?int $ignoreAssignmentId = null): bool
    {
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
            $query->where(function ($q) use ($startAt) {
                $q->whereNull('ended_at')
                    ->orWhere('ended_at', '>=', $startAt);
            });
        }

        return $query->exists();
    }
}
