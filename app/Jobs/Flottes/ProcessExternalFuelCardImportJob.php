<?php

namespace App\Jobs\Flottes;

use App\Models\Flottes\Vehicle;
use App\Models\User;
use App\Notifications\Flottes\FuelAnomalyAlertNotification;
use App\Services\Flottes\VehicleFuelService;
use DB;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;
use Log;

class ProcessExternalFuelCardImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected array $transactions) {}

    public function handle(VehicleFuelService $fuelService): void
    {
        $managers = User::where('is_admin', true)->get();
        $processedCount = 0;
        $errorCount = 0;

        foreach ($this->transactions as $transaction) {
            try {
                // Normalisation plaque
                $licensePlate = strtoupper(str_replace([' ', '-'], '', $transaction['license_plate']));
                $vehicle = Vehicle::where('license_plate', $licensePlate)->first();

                if (! $vehicle) {
                    Log::warning("Import carburant : Véhicule non trouvé {$transaction['license_plate']}");
                    $errorCount++;

                    continue;
                }

                DB::transaction(function () use ($vehicle, $transaction, $fuelService, $managers) {
                    // Analyse et audit de la transaction
                    $fuelService->processAndAuditFuelTransaction(
                        $vehicle,
                        (float) $transaction['liters'],
                        (float) $transaction['cost_ht'],
                        (float) $transaction['odometer'],
                        now()->parse($transaction['date']),
                        $transaction['station_name'] ?? 'Import carte carburant'
                    );

                    // Détection consommation aberrante (> 15 L/100km)
                    $analysis = $fuelService->logFuelConsumption(
                        $vehicle,
                        (float) $transaction['liters'],
                        (float) $transaction['cost_ht'],
                        (float) $transaction['odometer'],
                        now()->parse($transaction['date'])
                    );

                    if ($analysis['average_consumption_100km'] > 15.0) {
                        Notification::send(
                            $managers,
                            new FuelAnomalyAlertNotification(
                                $vehicle,
                                "Consommation excessive : {$analysis['average_consumption_100km']} L/100km ({$transaction['liters']}L / {$analysis['distance_travelled']}km)"
                            )
                        );
                    }

                    // Détection odomètre incohérent (> 2000 km entre 2 pleins)
                    if ($analysis['distance_travelled'] > 2000) {
                        Notification::send(
                            $managers,
                            new FuelAnomalyAlertNotification(
                                $vehicle,
                                "Écart odomètre suspect : {$analysis['distance_travelled']} km entre pleins"
                            )
                        );
                    }

                    // Prix unitaire anormal
                    if ($analysis['cost_per_km'] > 0.5) {
                        Log::warning("Prix carburant élevé : {$vehicle->reference} @ {$analysis['cost_per_km']}€/km");
                    }
                });

                $processedCount++;
            } catch (Exception $e) {
                Log::error("Erreur import carburant : {$e->getMessage()}");
                $errorCount++;
            }
        }

        Log::info("Import carburant : {$processedCount} transactions traitées, {$errorCount} erreurs");
    }

    public function failed(Exception $exception): void
    {
        Log::error("Job import carburant échoué : {$exception->getMessage()}");
    }
}
