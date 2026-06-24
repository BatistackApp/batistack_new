<?php

namespace App\Jobs\Flottes;

use App\Models\Flottes\Vehicle;
use App\Services\Flottes\FleetDocumentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Log;

class GenerateFleetReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(FleetDocumentService $documentService): void
    {
        $vehicles = Vehicle::all();
        $from = now()->subMonth();
        $to = now();

        foreach ($vehicles as $vehicle) {
            try {
                // Génération rapport TCO
                $vehicleFiche = $documentService->generateVehicleFiche($vehicle);

                // Génération rapport maintenance
                $maintenanceReport = $documentService->generateMaintenanceReport($vehicle, $from, $to);

                // Génération rapport consommation
                $consumptionReport = $documentService->generateConsumptionReport($vehicle, $from, $to);

                // Génération rapport utilisation
                $usageReport = $documentService->generateUsageReport($vehicle, $from, $to);

                // Stockage des rapports
                $this->storeReports($vehicle, [
                    'fiche' => $vehicleFiche,
                    'maintenance' => $maintenanceReport,
                    'consumption' => $consumptionReport,
                    'usage' => $usageReport,
                ]);

                Log::info("Rapports générés pour {$vehicle->reference}");
            } catch (\Exception $e) {
                Log::error("Erreur génération rapports {$vehicle->reference} : {$e->getMessage()}");
            }
        }

        Log::info('Génération rapports flotte complétée');
    }

    protected function storeReports(Vehicle $vehicle, array $reports): void
    {
        $path = "fleet_reports/{$vehicle->reference}/".now()->format('Y-m-d');

        foreach ($reports as $name => $data) {
            $filename = "{$name}_".now()->format('Y-m-d_H-i-s').'.json';
            Storage::put("{$path}/{$filename}", json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}
