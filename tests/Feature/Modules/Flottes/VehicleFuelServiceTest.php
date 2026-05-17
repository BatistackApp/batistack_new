<?php

use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\Vehicle;
use App\Services\Flottes\VehicleFuelService;

beforeEach(function () {
    // Résolution du service de carburant à tester
    $this->fuelService = app(VehicleFuelService::class);

    // Initialisation d'un véhicule disponible avec un odomètre de départ à 50 000 km
    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-FUEL-TEST',
        'license_plate' => 'AA123BB',
        'brand' => 'Renault',
        'model' => 'Master',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 50000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 28000.00,
    ]);
});

describe('VehicleFuelService - Suivi Carburant & Énergie', function () {

    test('il calcule les ratios d’efficience de consommation lors de l’enregistrement d’un plein standard', function () {
        // Enregistrement d'un plein à 50 500 km (soit 500 km parcourus)
        // Consommation : 40 Litres pour un coût de 70 € HT
        $analysis = $this->fuelService->logFuelConsumption(
            $this->vehicle,
            40.0,      // Litres
            70.00,     // Coût HT
            50500.00,  // Odomètre au moment du plein
            now()
        );

        // Consommation moyenne aux 100km : (40L / 500km) * 100 = 8.0 L/100km
        // Coût kilométrique réel : 70€ / 500km = 0.14 €/km
        expect($analysis)->toBeArray()
            ->and($analysis['distance_travelled'])->toEqual(500.00)
            ->and($analysis['average_consumption_100km'])->toEqual(8.00)
            ->and($analysis['cost_per_km'])->toEqual(0.1400)
            ->and($this->vehicle->refresh()->odometer)->toEqual(50500.00);

        // L'odomètre global du véhicule doit être mis à jour en base de données

        $this->assertDatabaseHas('vehicles', [
            'id' => $this->vehicle->id,
            'odometer' => 50500.00,
        ]);
    });

    test('il gère correctement le cas d’une distance nulle (consommation ratio de 0.0 et coût par km de 0.0)', function () {
        // Enregistrement d'un plein sans que le véhicule n'ait roulé (ex: double saisie ou complément)
        // Odomètre identique au kilométrage actuel (50 000 km)
        $analysis = $this->fuelService->logFuelConsumption(
            $this->vehicle,
            5.0,       // Litres
            9.00,      // Coût HT
            50000.00,  // Odomètre identique
            now()
        );

        expect($analysis)->toBeArray()
            ->and($analysis['distance_travelled'])->toEqual(0.0)
            ->and($analysis['average_consumption_100km'])->toEqual(0.0)
            ->and($analysis['cost_per_km'])->toEqual(0.0)
            ->and($this->vehicle->refresh()->odometer)->toEqual(50000.00);

        // L'odomètre du véhicule ne doit pas avoir changé
    });

    test('il refuse la saisie d’un plein si l’odomètre est inférieur au kilométrage de référence du véhicule', function () {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("L'odomètre saisi lors du plein (49900 km) ne peut pas être inférieur au kilométrage actuel du véhicule (50000.00 km).");

        // Odomètre de départ = 50 000 km. Tentative d'enregistrement d'un plein à 49 900 km (odomètre régressif)
        $this->fuelService->logFuelConsumption(
            $this->vehicle,
            30.0,
            50.00,
            49900.00,
            now()
        );
    });
});
