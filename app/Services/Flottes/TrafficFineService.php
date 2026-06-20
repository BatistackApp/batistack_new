<?php

namespace App\Services\Flottes;

use App\Enums\Flottes\FineStatus;
use App\Models\Flottes\TrafficFine;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TrafficFineService
{
    /**
     * Enregistre un PV.
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

        $driverId = $this->resolveDriverForFine($fine);
        if ($driverId) {
            $fine->update(['employee_id' => $driverId]);
        }

        return $fine;
    }

    /**
     * Retrouve le conducteur à la date de l'infraction.
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

    /**
     * Marque une amende comme payée.
     */
    public function markAsPaid(TrafficFine $fine): TrafficFine
    {
        $fine->update(['status' => FineStatus::PAID]);

        return $fine;
    }

    /**
     * Marque une amende comme contestée.
     */
    public function markAsDisputed(TrafficFine $fine): TrafficFine
    {
        $fine->update(['status' => FineStatus::DISPUTED]);

        return $fine;
    }

    /**
     * Marque une amende comme transmise au conducteur.
     */
    public function markAsTransmitted(TrafficFine $fine): TrafficFine
    {
        $fine->update(['status' => FineStatus::TRANSMITTED]);

        return $fine;
    }

    /**
     * Obtient les amendes en attente de paiement.
     */
    public function getPendingFines(Vehicle $vehicle): Collection
    {
        return $vehicle->fines()
            ->whereIn('status', [FineStatus::RECEIVED, FineStatus::DISPUTED])
            ->orderByDesc('infraction_at')
            ->get();
    }

    /**
     * Calcule le total des amendes impayées.
     */
    public function getPendingFinesTotal(Vehicle $vehicle): float
    {
        return (float) $this->getPendingFines($vehicle)->sum('amount');
    }

    /**
     * Obtient l'historique complet des amendes.
     */
    public function getFineHistory(Vehicle $vehicle): Collection
    {
        return $vehicle->fines()
            ->orderByDesc('infraction_at')
            ->with('employee')
            ->get();
    }

    /**
     * Calcule les points de permis à risque.
     */
    public function getTotalPointsDeducted(Vehicle $vehicle, Carbon $from, Carbon $to): int
    {
        return (int) $vehicle->fines()
            ->whereBetween('infraction_at', [$from, $to])
            ->sum('points_deducted');
    }

    /**
     * Obtient les statistiques des amendes.
     */
    public function getFineStatistics(Vehicle $vehicle): array
    {
        $allFines = $vehicle->fines()->get();
        $pendingFines = $this->getPendingFines($vehicle);
        $paidFines = $vehicle->fines()->where('status', FineStatus::PAID)->get();

        return [
            'total_fines' => $allFines->count(),
            'pending_fines' => $pendingFines->count(),
            'pending_amount' => (float) $pendingFines->sum('amount'),
            'paid_fines' => $paidFines->count(),
            'paid_amount' => (float) $paidFines->sum('amount'),
            'total_points_deducted' => (int) $allFines->sum('points_deducted'),
            'average_fine_amount' => (float) ($allFines->count() > 0 ? $allFines->sum('amount') / $allFines->count() : 0),
        ];
    }

    /**
     * Détecte les récidivistes.
     */
    public function isRecidivistDriver(Vehicle $vehicle): bool
    {
        return $vehicle->fines()
            ->whereYear('infraction_at', now()->year)
            ->count() >= 3;
    }
}
