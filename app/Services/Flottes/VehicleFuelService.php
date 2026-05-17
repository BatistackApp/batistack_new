<?php

namespace App\Services\Flottes;

use App\Models\Flottes\Vehicle;
use Carbon\Carbon;
use DB;
use Exception;

class VehicleFuelService
{
    /**
     * Enregistre la consommation d'essence et analyse la rentabilité kilométrique.
     * Met également à jour l'odomètre général du véhicule.
     * @throws Exception
     */
    public function logFuelConsumption(
        Vehicle $vehicle,
        float $liters,
        float $costHt,
        float $odometerAtPlein,
        Carbon $date
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
}
