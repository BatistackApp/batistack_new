<?php


use App\Enums\Flottes\VehicleStatus;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\Tiers\ThirdParty;
use App\Services\Flottes\FleetCostService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->costService = app(FleetCostService::class);

    // Initialisation des données requises pour les relations
    $this->vatRate = VatRate::create([
        'name' => 'TVA 20%',
        'rate' => 20.0000,
        'is_default' => true,
    ]);

    $this->garage = ThirdParty::create([
        'name' => 'Garage Central BTP',
        'type' => 'supplier',
        'is_active' => true,
    ]);

    // Création d'un véhicule test conforme à la structure
    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-COST-01',
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 15000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 30000.00,
    ]);
});

describe('FleetCostService - Analyse Financière & Maintenance', function () {

    test('il calcule le TCO d’un véhicule en agrégeant l’achat, les contrats et les maintenances', function () {
        // Enregistrement d'une maintenance de 450 € HT via le service
        $this->costService->logMaintenance(
            $this->vehicle,
            $this->garage,
            $this->vatRate,
            'Vidange & Changement disques',
            450.0000,
            now()
        );

        // Ajout d'un contrat d'assurance / leasing de 2400 € HT
        $this->vehicle->contracts()->create([
            'supplier_id' => $this->garage->id,
            'type' => 'leasing',
            'policy_number' => 'LEASE-BOXER',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'annual_cost_ht' => 2400.00,
        ]);

        // Calcul attendu : Achat (30000) + Maintenance (450) + Contrat (2400) = 32850 € HT
        $tco = $this->costService->calculateTco($this->vehicle);

        expect($tco)->toBe(32850.00);
    });

    test('il peut logguer une maintenance et remplit correctement l’odomètre au moment de l’intervention', function () {
        $performedAt = Carbon::parse('2026-05-17');

        $maintenance = $this->costService->logMaintenance(
            $this->vehicle,
            $this->garage,
            $this->vatRate,
            'Contrôle technique',
            85.0000,
            $performedAt,
            'Passage annuel obligatoire'
        );

        expect($maintenance)->toBeInstanceOf(VehicleMaintenance::class)
            ->and($maintenance->cost_ht)->toEqual(85.0000)
            ->and($maintenance->odometer_at_maintenance)->toEqual(15000.00)
            ->and($maintenance->performed_at->toDateString())->toBe('2026-05-17');

        $this->assertDatabaseHas('vehicle_maintenances', [
            'id' => $maintenance->id,
            'vehicle_id' => $this->vehicle->id,
            'cost_ht' => 85.0000,
            'odometer_at_maintenance' => 15000.00,
        ]);
    });

    test('il bascule le véhicule en statut MAINTENANCE pour les types d’intervention critiques', function (string $type) {
        expect($this->vehicle->status)->toBe(VehicleStatus::AVAILABLE);

        $this->costService->logMaintenance(
            $this->vehicle,
            $this->garage,
            $this->vatRate,
            $type,
            750.0000,
            now()
        );

        // Le véhicule doit être passé sous le statut MAINTENANCE
        expect($this->vehicle->refresh()->status)->toBe(VehicleStatus::MAINTENANCE);
    })->with([
        'réparation',
        'PANNE',
        'Accident',
    ]);

    test('il laisse le véhicule disponible pour les maintenances légères ou standard', function () {
        expect($this->vehicle->status)->toBe(VehicleStatus::AVAILABLE);

        $this->costService->logMaintenance(
            $this->vehicle,
            $this->garage,
            $this->vatRate,
            'Lavage complet et essuyage',
            35.0000,
            now()
        );

        // Le statut ne doit pas avoir bougé
        expect($this->vehicle->refresh()->status)->toBe(VehicleStatus::AVAILABLE);
    });
});
