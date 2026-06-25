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
use Illuminate\Database\Eloquent\Collection;

class VehicleAssignmentService
{
    public function __construct(
        protected FleetCostService $costService
    ) {}

    /**
     * Crée une nouvelle affectation avec contrôles stricts.
     * @throws Exception|\Throwable
     */
    public function createAssignment(
        Vehicle $vehicle,
        Employee $employee,
        ?Chantier $chantier,
        Carbon|CarbonInterface $startedAt,
        Carbon|CarbonInterface|null $endedAt,
        ?string $purpose = null
    ): VehicleAssignment {

        $this->validateDriverCompliance($employee, $vehicle);
        $this->validateZfeCompliance($vehicle, $chantier);

        if ($this->hasConflict($vehicle->id, $employee->id, $startedAt, $endedAt)) {
            throw new Exception("Conflit d'affectation détecté : véhicule ou salarié déjà mobilisé.");
        }

        if ($vehicle->status === VehicleStatus::BROKEN) {
            throw new Exception('Le véhicule est actuellement en panne.');
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
     * Clôture une affectation.
     * @throws Exception
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
            throw new Exception('Le kilométrage de retour ne peut pas être inférieur au kilométrage de départ.');
        }

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
     * Valide l'aptitude du conducteur.
     * @throws Exception
     */
    protected function validateDriverCompliance(Employee $employee, Vehicle $vehicle): void
    {
        $lastVisit = $employee->medicalVisits()->latest('visit_date')->first();
        if (! $lastVisit || $lastVisit->isExpired() || $lastVisit->aptitude === MedicalAptitude::UNFIT) {
            throw new Exception("Aptitude médicale invalide pour {$employee->getFullName()}.");
        }

        if ($vehicle->type === VehicleType::SPECIAL) {
            $hasCaces = $employee->qualifications()
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->where('type', QualificationType::CACES)
                ->exists();

            if (! $hasCaces) {
                throw new Exception("{$employee->getFullName()} ne possède pas de CACES valide.");
            }
        } else {
            $hasLicense = $employee->qualifications()
                ->where(function ($q) {
                    $q->whereNull('expires_at')
                        ->orWhere('expires_at', '>', Carbon::now());
                })
                ->where('type', QualificationType::PERMIS)
                ->exists();

            if (! $hasLicense) {
                throw new Exception("Aucun permis de conduire valide pour {$employee->getFullName()}.");
            }
        }
    }

    /**
     * Valide la conformité ZFE.
     * @throws Exception
     */
    protected function validateZfeCompliance(Vehicle $vehicle, ?Chantier $chantier): void
    {
        if (! $chantier || empty($chantier->city)) {
            return;
        }

        $city = strtolower(trim($chantier->city));
        $vehicleCritAir = strtoupper($vehicle->crit_air_level ?? '2');

        $zfeRegulations = [
            'paris' => '2',
            'lyon' => '2',
            'marseille' => '2',
            'strasbourg' => '3',
            'toulouse' => '3',
            'nice' => '3',
            'grenoble' => '3',
        ];

        if (array_key_exists($city, $zfeRegulations)) {
            $maxAllowed = $zfeRegulations[$city];
            $ranks = ['E' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5];

            $vehicleRank = $ranks[$vehicleCritAir] ?? 5;
            $maxRank = $ranks[$maxAllowed] ?? 5;

            if ($vehicleRank > $maxRank) {
                throw new Exception("ZFE non conforme : {$vehicle->reference} (Crit'Air {$vehicleCritAir}) non autorisé à {$city}.");
            }
        }
    }

    /**
     * Obtient l'inventaire du véhicule.
     */
    public function getOnboardInventory(Vehicle $vehicle): Collection
    {
        return $vehicle->inventories()->with('item')->get();
    }

    /**
     * Impute le coût au chantier.
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

        $amortizationRate = $totalOdometer > 0 ? ($tco / $totalOdometer) : 0;
        $totalImputedCost = $distance * ($kmRate + $amortizationRate);

        logger()->info("Imputation : {$vehicle->reference} coût {$totalImputedCost}€ HT pour chantier #{$assignment->chantier_id}.");
    }

    /**
     * Détecte les chevauchements.
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

    /**
     * Obtient les affectations actives.
     */
    public function getActiveAssignments(): Collection
    {
        return VehicleAssignment::active()
            ->with('vehicle', 'employee', 'chantier')
            ->get();
    }

    /**
     * Obtient l'affectation active d'un véhicule.
     */
    public function getActiveAssignmentForVehicle(Vehicle $vehicle): ?VehicleAssignment
    {
        return $vehicle->currentAssignment;
    }

    /**
     * Obtient les affectations complétées avec coûts.
     */
    public function getCompletedAssignmentsWithCosts(CarbonInterface $from, CarbonInterface $to): Collection
    {
        return VehicleAssignment::completed()
            ->whereBetween('ended_at', [$from, $to])
            ->with('vehicle', 'employee', 'chantier')
            ->get();
    }

    /**
     * Calcule les statistiques d'utilisation.
     */
    public function getUtilizationStatistics(Vehicle $vehicle): array
    {
        $assignments = $vehicle->assignments()->get();

        return [
            'total_assignments' => $assignments->count(),
            'active_assignments' => $assignments->where('status', AssignmentStatus::ACTIVE)->count(),
            'completed_assignments' => $assignments->where('status', AssignmentStatus::COMPLETED)->count(),
            'total_distance' => (float) $assignments->sum(function ($a) {
                return $a->getDistance();
            }),
            'total_hours' => (float) $assignments->sum(function ($a) {
                return $a->getDurationInHours() ?? 0;
            }),
            'total_cost' => (float) $assignments->sum(function ($a) {
                return $a->getCost();
            }),
        ];
    }
}
