<?php

namespace App\Jobs\Flottes;

use App\Models\Flottes\Vehicle;
use App\Services\Flottes\VehicleFuelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Log;

class AnalyzeFuelConsumptionTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(VehicleFuelService $fuelService): void
    {
        $vehicles = Vehicle::all();

        foreach ($vehicles as $vehicle) {
            try {
                // Analyse 3 mois
                $stats = $fuelService->getConsumptionStatistics($vehicle);

                if ($stats['monthly_consumption']) {
                    // Stockage en cache pour rapide accès
                    Cache::put(
                        "fuel_stats_{$vehicle->id}",
                        $stats,
                        now()->addDays(7)
                    );

                    // Détection anomalies
                    if ($stats['suspicious_transactions_count'] > 0) {
                        Log::warning("Consommation suspecte : {$vehicle->reference} - {$stats['suspicious_transactions_count']} transactions");
                    }

                    // Tendance hausse consommation
                    if ($stats['three_months_consumption'] &&
                        $stats['monthly_consumption'] > $stats['three_months_consumption'] * 1.2) {
                        Log::warning("Hausse consommation : {$vehicle->reference} +20% vs 3 mois");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Analyse consommation {$vehicle->reference} : {$e->getMessage()}");
            }
        }

        Log::info('Analyse consommation complétée pour '.count($vehicles).' véhicules');
    }
}
