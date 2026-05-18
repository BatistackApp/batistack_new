<?php

use App\Enums\Flottes\VehicleStatus;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\Tiers\ThirdParty;
use App\Services\Flottes\VehicleAlertService;

beforeEach(function () {
    // Initialisation du service à tester
    $this->alertService = app(VehicleAlertService::class);

    // Données de base requises pour les relations
    $this->vatRate = VatRate::create([
        'name' => 'TVA 20%',
        'rate' => 20.0000,
        'is_default' => true,
    ]);

    $this->supplier = ThirdParty::create([
        'name' => 'Partenaire Flotte Assurance',
        'type' => 'supplier',
        'is_active' => true,
    ]);

    // Création du véhicule de référence
    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-ALERT-99',
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Partner',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 10000.00, // Démarre à 10 000 km
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 18000.00,
    ]);
});

describe('VehicleAlertService - Surveillance administrative et maintenance préventive', function () {

    // ==========================================
    // TESTS : getExpiringContracts()
    // ==========================================
    describe('getExpiringContracts() - Alertes échéances assurances et leasing', function () {

        test('il identifie et retourne les contrats arrivant à échéance sous N jours', function () {
            // Création d'un contrat expirant dans 15 jours (doit être capturé à J-30)
            $expiringContract = VehicleContract::create([
                'vehicle_id' => $this->vehicle->id,
                'supplier_id' => $this->supplier->id,
                'type' => 'insurance',
                'policy_number' => 'POL-EXP-15',
                'start_date' => now()->subYear(),
                'end_date' => now()->addDays(15),
                'annual_cost_ht' => 800.00,
            ]);

            // Création d'un contrat expirant dans 45 jours (hors plage J-30, ne doit pas être capturé)
            VehicleContract::create([
                'vehicle_id' => $this->vehicle->id,
                'supplier_id' => $this->supplier->id,
                'type' => 'leasing',
                'policy_number' => 'POL-OK-45',
                'start_date' => now(),
                'end_date' => now()->addDays(45),
                'annual_cost_ht' => 2400.00,
            ]);

            // Création d'un contrat déjà expiré (ne doit pas être retourné car end_date <= now() mais getExpiringContracts exclut le passé)
            VehicleContract::create([
                'vehicle_id' => $this->vehicle->id,
                'supplier_id' => $this->supplier->id,
                'type' => 'insurance',
                'policy_number' => 'POL-PAST-10',
                'start_date' => now()->subYears(2),
                'end_date' => now()->subDays(10),
                'annual_cost_ht' => 800.00,
            ]);

            $results = $this->alertService->getExpiringContracts(30);

            expect($results)->toHaveCount(1)
                ->and($results->first()->policy_number)->toBe('POL-EXP-15')
                ->and($results->first()->vehicle->id)->toBe($this->vehicle->id);
        });

        test('il retourne une collection vide si aucun contrat n’est sur le point d’expirer', function () {
            // Contrat permanent ou longue durée (expire dans 365 jours)
            VehicleContract::create([
                'vehicle_id' => $this->vehicle->id,
                'supplier_id' => $this->supplier->id,
                'type' => 'insurance',
                'policy_number' => 'POL-FAR-FUTURE',
                'start_date' => now(),
                'end_date' => now()->addYear(),
                'annual_cost_ht' => 900.00,
            ]);

            $results = $this->alertService->getExpiringContracts(30);

            expect($results)->toBeEmpty();
        });
    });

    // ==========================================
    // TESTS : needsMaintenance()
    // ==========================================
    describe('needsMaintenance() - Jalons de révision kilométrique', function () {

        test('il calcule les besoins de révision par rapport à l’odomètre de départ si aucune maintenance n’est enregistrée', function () {
            // Le véhicule a un compteur à 10 000 km.
            // Si le jalon est de 20 000 km : pas besoin de maintenance (10000 < 20000)
            expect($this->alertService->needsMaintenance($this->vehicle, 20000.00))->toBeFalse();

            // Si on force l'odomètre du véhicule à 25 000 km sans historique d'entretien : maintenance requise (25000 >= 20000)
            $this->vehicle->update(['odometer' => 25000.00]);
            expect($this->alertService->needsMaintenance($this->vehicle, 20000.00))->toBeTrue();
        });

        test('il lève une alerte de maintenance si la distance parcourue depuis le dernier entretien dépasse le seuil', function () {
            // 1. Premier entretien enregistré à 10 000 km
            VehicleMaintenance::create([
                'vehicle_id' => $this->vehicle->id,
                'supplier_id' => $this->supplier->id,
                'vat_rate_id' => $this->vatRate->id,
                'type' => 'Vidange standard',
                'cost_ht' => 150.00,
                'odometer_at_maintenance' => 10000.00,
                'performed_at' => now()->subMonths(6),
            ]);

            // Seuil de révision configuré à 15 000 km d'écart.
            // Cas A : Le véhicule roule et atteint 22 000 km (Écart de 12 000 km depuis la vidange < 15 000 km) -> Pas d'alerte
            $this->vehicle->update(['odometer' => 22000.00]);
            expect($this->alertService->needsMaintenance($this->vehicle, 15000.00))->toBeFalse();

            // Cas B : Le véhicule atteint 26 000 km (Écart de 16 000 km depuis la vidange >= 15 000 km) -> ALERTE !
            $this->vehicle->update(['odometer' => 26000.00]);
            expect($this->alertService->needsMaintenance($this->vehicle, 15000.00))->toBeTrue();
        });

        test('il se base toujours sur l’entretien physique le plus récent pour calculer l’écart', function () {
            // 1. Entretien ancien à 5 000 km
            VehicleMaintenance::create([
                'vehicle_id' => $this->vehicle->id,
                'supplier_id' => $this->supplier->id,
                'vat_rate_id' => $this->vatRate->id,
                'type' => 'Révision de rodage',
                'cost_ht' => 100.00,
                'odometer_at_maintenance' => 5000.00,
                'performed_at' => now()->subYear(),
            ]);

            // 2. Entretien récent à 12 000 km
            VehicleMaintenance::create([
                'vehicle_id' => $this->vehicle->id,
                'supplier_id' => $this->supplier->id,
                'vat_rate_id' => $this->vatRate->id,
                'type' => 'Remplacement pneumatiques',
                'cost_ht' => 300.00,
                'odometer_at_maintenance' => 12000.00,
                'performed_at' => now()->subMonth(), // Le plus récent dans le temps
            ]);

            // Odomètre actuel du véhicule à 20 000 km.
            // Calcul par rapport au dernier entretien : 20 000 - 12 000 = 8 000 km.
            // Si l'intervalle est de 10 000 km : pas d'alerte (8000 < 10000)
            $this->vehicle->update(['odometer' => 20000.00]);
            expect($this->alertService->needsMaintenance($this->vehicle, 10000.00))->toBeFalse();

            // Si le véhicule atteint 23 000 km : écart de 11 000 km (11000 >= 10000) -> ALERTE !
            $this->vehicle->update(['odometer' => 23000.00]);
            expect($this->alertService->needsMaintenance($this->vehicle, 10000.00))->toBeTrue();
        });
    });
});
