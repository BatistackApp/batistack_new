<?php

namespace App\Services\Flottes;

use App\Enums\Flottes\FineStatus;
use App\Models\Flottes\TrafficFine;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use Carbon\Carbon;

class TrafficFineService
{
    /**
     * Enregistre un PV et tente d'identifier automatiquement le conducteur.
     */
    public function registerFine(
        Vehicle $vehicle,
        string $reference,
        Carbon $infractionAt,
        float $amount,
        int $pointsDeducted = 0
    ): TrafficFine {
        $fine = TrafficFine::create([
            'vehicle_id' => $vehicle->id,
            'reference' => $reference,
            'infraction_at' => $infractionAt,
            'amount' => $amount,
            'points_deducted' => $pointsDeducted,
            'status' => FineStatus::RECEIVED,
        ]);

        // Résolution automatique du conducteur
        $driverId = $this->resolveDriverForFine($fine);
        if ($driverId) {
            $fine->update(['employee_id' => $driverId]);
        }

        return $fine;
    }

    /**
     * Retrouve l'employé qui conduisait le véhicule à la date précise de l'infraction.
     */
    public function resolveDriverForFine(TrafficFine $fine): ?int
    {
        $assignment = VehicleAssignment::where('vehicle_id', $fine->vehicle_id)
            ->where('started_at', '<=', $fine->infraction_at)
            ->where(function ($query) use ($fine) {
                $query->where('ended_at', '>=', $fine->infraction_at)
                    ->orWhereNull('ended_at');
            })
            ->first();

        return $assignment?->employee_id;
    }
}
