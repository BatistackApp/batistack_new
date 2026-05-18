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
        $managers = User::all();

        foreach ($this->transactions as $transaction) {
            // Recherche du véhicule par plaque d'immatriculation normalisée
            $licensePlate = strtoupper(str_replace([' ', '-'], '', $transaction['license_plate']));
            $vehicle = Vehicle::where('license_plate', $licensePlate)->first();

            if (! $vehicle) {
                Log::warning("Import Carburant : Aucun véhicule trouvé avec l'immatriculation : {$transaction['license_plate']}");

                continue;
            }

            try {
                DB::transaction(function () use ($vehicle, $transaction, $fuelService, $managers) {
                    // Analyse énergétique de la transaction
                    $analysis = $fuelService->logFuelConsumption(
                        $vehicle,
                        (float) $transaction['liters'],
                        (float) $transaction['cost_ht'],
                        (float) $transaction['odometer'],
                        now()->parse($transaction['date'])
                    );

                    // ALGORITHME DE DÉTECTION DES FRAUDES / COHÉRENCE :
                    // 1. Détection de consommation aberrante (ex: > 15L / 100km pour un utilitaire standard)
                    if ($analysis['average_consumption_100km'] > 15.0) {
                        Notification::send(
                            $managers,
                            new FuelAnomalyAlertNotification(
                                $vehicle,
                                'Consommation excessive détectée : '.$analysis['average_consumption_100km'].' L/100km (Distance : '.$analysis['distance_travelled'].' km, Volume : '.$transaction['liters'].'L).'
                            )
                        );
                    }

                    // 2. Détection d'odomètre incohérent (Saisie manuelle erronée à la station)
                    if ($analysis['distance_travelled'] > 2000) {
                        Notification::send(
                            $managers,
                            new FuelAnomalyAlertNotification(
                                $vehicle,
                                "Anomalie d'odomètre : Écart de ".$analysis['distance_travelled'].' km constaté depuis le dernier plein.'
                            )
                        );
                    }
                });

            } catch (Exception $e) {
                Log::error("Échec du traitement de transaction carburant pour le véhicule {$vehicle->reference} : ".$e->getMessage());
            }
        }
    }
}
