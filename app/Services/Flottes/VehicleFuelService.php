<?php

namespace App\Services\Flottes;

use App\Enums\RH\TimeEntryStatus;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Notifications\Flottes\FuelAnomalyAlertNotification;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use DB;
use Exception;
use Illuminate\Support\Facades\Notification;

class VehicleFuelService
{
    /**
     * Enregistre la consommation d'essence.
     *
     * @throws Exception|\Throwable
     */
    public function logFuelConsumption(
        Vehicle $vehicle,
        float $liters,
        float $costHt,
        float $odometerAtPlein,
        Carbon|CarbonInterface $date
    ): array {
        if ($odometerAtPlein < $vehicle->odometer) {
            throw new Exception("L'odomètre saisi ({$odometerAtPlein} km) ne peut pas être inférieur au kilométrage actuel ({$vehicle->odometer} km).");
        }

        $distance = $odometerAtPlein - $vehicle->odometer;
        $consumptionRatio = 0.0;

        if ($distance > 0) {
            $consumptionRatio = ($liters / $distance) * 100;
        }

        DB::transaction(function () use ($vehicle, $odometerAtPlein) {
            $vehicle->update(['odometer' => $odometerAtPlein]);
        });

        return [
            'distance_travelled' => $distance,
            'average_consumption_100km' => round($consumptionRatio, 2),
            'cost_per_km' => $distance > 0 ? round($costHt / $distance, 4) : 0.0,
        ];
    }

    /**
     * Traite et audite une transaction carburant.
     *
     * @throws \Throwable
     */
    public function processAndAuditFuelTransaction(
        Vehicle $vehicle,
        float $liters,
        float $costHt,
        float $odometer,
        Carbon|CarbonInterface $purchasedAt,
        string $stationName
    ): FuelTransaction {
        return DB::transaction(function () use ($vehicle, $liters, $costHt, $odometer, $purchasedAt, $stationName) {

            $assignment = VehicleAssignment::where('vehicle_id', $vehicle->id)
                ->where('started_at', '<=', $purchasedAt)
                ->where(function ($q) use ($purchasedAt) {
                    $q->where('ended_at', '>=', $purchasedAt)
                        ->orWhereNull('ended_at');
                })
                ->first();

            $driverId = $assignment?->employee_id;
            $isSuspicious = false;
            $suspicionReason = null;

            if ($driverId) {
                $hasWorkEntry = TimeEntry::where('employee_id', $driverId)
                    ->whereDate('date', $purchasedAt->toDateString())
                    ->whereIn('status', [TimeEntryStatus::SUBMITTED, TimeEntryStatus::APPROVED])
                    ->exists();

                if ($purchasedAt->isSunday()) {
                    $isSuspicious = true;
                    $suspicionReason = "Plein d'essence effectué un dimanche pour {$vehicle->reference}.";
                } elseif (! $hasWorkEntry) {
                    $driver = $assignment->employee;
                    $suspicionReason = 'Plein enregistré le '.$purchasedAt->format('d/m/Y')." mais aucun pointage RH pour {$driver->getFullName()}.";
                    $isSuspicious = true;
                }
            } else {
                $isSuspicious = true;
                $suspicionReason = 'Transaction carburant sans affectation de conducteur active (Siphonnage suspectée).';
            }

            $transaction = FuelTransaction::create([
                'vehicle_id' => $vehicle->id,
                'employee_id' => $driverId,
                'liters' => $liters,
                'cost_ht' => $costHt,
                'odometer' => $odometer,
                'purchased_at' => $purchasedAt,
                'station_name' => $stationName,
                'is_suspicious' => $isSuspicious,
                'suspicion_reason' => $suspicionReason,
            ]);

            if ($isSuspicious) {
                $managers = User::where('is_admin', true)->get();
                Notification::send($managers, new FuelAnomalyAlertNotification($vehicle, $suspicionReason));
            }

            if ($odometer > $vehicle->odometer) {
                $vehicle->updateQuietly(['odometer' => $odometer]);
            }

            return $transaction;
        });
    }

    /**
     * Calcule la consommation moyenne sur une période.
     */
    public function getAverageConsumption(Vehicle $vehicle, CarbonInterface $from, CarbonInterface $to): ?float
    {
        $transactions = $vehicle->fuelTransactions()
            ->whereBetween('purchased_at', [$from, $to])
            ->orderBy('purchased_at')
            ->get();

        if ($transactions->count() < 2) {
            return null;
        }

        $totalLiters = 0;
        $totalDistance = 0;

        for ($i = 1; $i < $transactions->count(); $i++) {
            $totalLiters += $transactions[$i]->liters;
            $totalDistance += $transactions[$i]->odometer - $transactions[$i - 1]->odometer;
        }

        return $totalDistance > 0 ? round(($totalLiters / $totalDistance) * 100, 2) : null;
    }

    /**
     * Détecte les anomalies de consommation.
     */
    public function detectConsumptionAnomaly(Vehicle $vehicle, float $currentConsumption, float $threshold = 20): bool
    {
        $avgConsumption = $this->getAverageConsumption($vehicle, now()->subMonths(3), now());

        if (! $avgConsumption) {
            return false;
        }

        $deviation = abs($currentConsumption - $avgConsumption) / $avgConsumption * 100;

        return $deviation > $threshold;
    }

    /**
     * Obtient le prix moyen au litre sur une période.
     */
    public function getAveragePricePerLiter(Vehicle $vehicle, Carbon $from, Carbon $to): float
    {
        $transactions = $vehicle->fuelTransactions()
            ->whereBetween('purchased_at', [$from, $to])
            ->get();

        if ($transactions->isEmpty()) {
            return 0;
        }

        $totalCost = $transactions->sum('cost_ht');
        $totalLiters = $transactions->sum('liters');

        return $totalLiters > 0 ? round($totalCost / $totalLiters, 3) : 0;
    }

    /**
     * Obtient les statistiques de consommation complètes.
     */
    public function getConsumptionStatistics(Vehicle $vehicle): array
    {
        $lastTransaction = $vehicle->fuelTransactions()
            ->orderByDesc('purchased_at')
            ->first();

        if (! $lastTransaction) {
            return [];
        }

        $monthAgo = now()->subMonth();
        $threeMonthsAgo = now()->subMonths(3);

        return [
            'last_refuel_date' => $lastTransaction->purchased_at,
            'last_refuel_liters' => $lastTransaction->liters,
            'last_refuel_cost' => $lastTransaction->cost_ht,
            'last_price_per_liter' => $lastTransaction->getPricePerLiter(),
            'monthly_consumption' => $this->getAverageConsumption($vehicle, $monthAgo, now()),
            'three_months_consumption' => $this->getAverageConsumption($vehicle, $threeMonthsAgo, now()),
            'monthly_avg_price_per_liter' => $this->getAveragePricePerLiter($vehicle, $monthAgo, now()),
            'suspicious_transactions_count' => $vehicle->fuelTransactions()
                ->where('is_suspicious', true)
                ->count(),
        ];
    }
}
