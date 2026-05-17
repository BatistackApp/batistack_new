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
     * Enregistre la consommation d'essence et analyse la rentabilité kilométrique.
     * Met également à jour l'odomètre général du véhicule.
     *
     * @throws Exception
     */
    public function logFuelConsumption(
        Vehicle $vehicle,
        float $liters,
        float $costHt,
        float $odometerAtPlein,
        Carbon|CarbonInterface $date
    ): array {
        if ($odometerAtPlein < $vehicle->odometer) {
            throw new Exception("L'odomètre saisi lors du plein ({$odometerAtPlein} km) ne peut pas être inférieur au kilométrage actuel du véhicule ({$vehicle->odometer} km).");
        }

        $distance = $odometerAtPlein - $vehicle->odometer;
        $consumptionRatio = 0.0;

        if ($distance > 0) {
            // Calcul de la consommation moyenne standard (Litres aux 100 km)
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

    public function processAndAuditFuelTransaction(
        Vehicle $vehicle,
        float $liters,
        float $costHt,
        float $odometer,
        Carbon|CarbonInterface $purchasedAt,
        string $stationName
    ): FuelTransaction {
        return DB::transaction(function () use ($vehicle, $liters, $costHt, $odometer, $purchasedAt, $stationName) {

            // 1. Recherche du chauffeur au moment précis du plein d'essence
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

            // 2. ANALYSE DU CRÉNEAU DE TRAVAIL (FRAUDE WEEK-END / ABSENCE DE POINTAGE)
            if ($driverId) {
                // Recherche d'un pointage d'heures de production actif ce jour-là
                $hasWorkEntry = TimeEntry::where('employee_id', $driverId)
                    ->whereDate('date', $purchasedAt->toDateString())
                    ->whereIn('status', [TimeEntryStatus::SUBMITTED, TimeEntryStatus::APPROVED])
                    ->exists();

                if ($purchasedAt->isSunday()) {
                    $isSuspicious = true;
                    $suspicionReason = "Plein d'essence effectué un dimanche pour l'actif {$vehicle->reference}.";
                } elseif (! $hasWorkEntry) {
                    $driver = $assignment->employee;
                    $suspicionReason = "Plein d'essence enregistré le ".$purchasedAt->format('d/m/Y')." mais aucun pointage d'heures RH n'est saisi pour {$driver->full_name} ce jour-là.";
                    $isSuspicious = true;
                }
            } else {
                // Siphonnage ou utilisation frauduleuse hors période de travail
                $isSuspicious = true;
                $suspicionReason = 'Transaction carburant enregistrée en station sans aucune affectation de conducteur active (Siphonnage ou utilisation frauduleuse suspectée).';
            }

            // 3. Enregistrement de la transaction
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

            // 4. Notification immédiate des gestionnaires de flotte si suspect
            if ($isSuspicious) {
                $managers = User::admin()->get(); // À filtrer par rôle approprié en production
                Notification::send($managers, new FuelAnomalyAlertNotification($vehicle, $suspicionReason));
            }

            // Mise à jour de l'odomètre réel du véhicule si supérieur
            if ($odometer > $vehicle->odometer) {
                $vehicle->updateQuietly(['odometer' => $odometer]);
            }

            return $transaction;
        });
    }
}
