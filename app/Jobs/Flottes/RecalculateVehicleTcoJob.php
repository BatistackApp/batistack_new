<?php

namespace App\Jobs\Flottes;

use App\Models\Flottes\Vehicle;
use App\Services\Flottes\FleetCostService;
use Cache;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class RecalculateVehicleTcoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected Vehicle $vehicle) {}

    public function handle(FleetCostService $costService): void
    {
        try {
            $tco = $costService->calculateTco($this->vehicle);

            $this->vehicle->updateQuietly([
                'tco_cache' => $tco,
            ]);

            Cache::put(
                "vehicle_tco_cache_{$this->vehicle->id}",
                $tco,
                now()->addDays(7)
            );

            Log::info("TCO recalculé asynchronement pour {$this->vehicle->reference} : {$tco} € HT.");
        } catch (Exception $e) {
            Log::error("Erreur de calcul TCO pour le véhicule #{$this->vehicle->id} : ".$e->getMessage());
        }
    }
}
