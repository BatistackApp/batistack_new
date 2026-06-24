<?php

use App\Enums\Flottes\VehicleStatus;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\Tiers\ThirdParty;
use App\Services\Flottes\VehicleAlertService;

beforeEach(function () {
    $this->alertService = app(VehicleAlertService::class);

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

    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-ALERT-99',
        'license_plate' => 'AA123BB',
        'brand' => 'Peugeot',
        'model' => 'Partner',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 10000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 18000.00,
    ]);
});

test('identifie contrats expirant -30j', function () {
    VehicleContract::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'type' => 'insurance',
        'policy_number' => 'POL-EXP-15',
        'start_date' => now()->subYear(),
        'end_date' => now()->addDays(15),
        'annual_cost_ht' => 800.00,
    ]);

    VehicleContract::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'type' => 'leasing',
        'policy_number' => 'POL-OK-45',
        'start_date' => now(),
        'end_date' => now()->addDays(45),
        'annual_cost_ht' => 2400.00,
    ]);

    $results = $this->alertService->getExpiringContracts(30);

    expect($results)->toHaveCount(1)
        ->and($results->first()->policy_number)->toBe('POL-EXP-15');
});

test('retourne collection vide si aucun contrat n\'expire', function () {
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

test('calcule besoins révision kilométrique', function () {
    expect($this->alertService->needsMaintenance($this->vehicle, 20000.00))->toBeFalse();

    $this->vehicle->update(['odometer' => 25000.00]);
    expect($this->alertService->needsMaintenance($this->vehicle, 20000.00))->toBeTrue();
});

test('lève alerte maintenance si distance dépasse seuil', function () {
    VehicleMaintenance::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'vat_rate_id' => $this->vatRate->id,
        'type' => 'Vidange standard',
        'cost_ht' => 150.00,
        'odometer_at_maintenance' => 10000.00,
        'performed_at' => now()->subMonths(6),
    ]);

    $this->vehicle->update(['odometer' => 22000.00]);
    expect($this->alertService->needsMaintenance($this->vehicle, 15000.00))->toBeFalse();

    $this->vehicle->update(['odometer' => 26000.00]);
    expect($this->alertService->needsMaintenance($this->vehicle, 15000.00))->toBeTrue();
});

test('se base sur entretien physique le plus récent', function () {
    VehicleMaintenance::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'vat_rate_id' => $this->vatRate->id,
        'type' => 'Révision de rodage',
        'cost_ht' => 100.00,
        'odometer_at_maintenance' => 5000.00,
        'performed_at' => now()->subYear(),
    ]);

    VehicleMaintenance::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'vat_rate_id' => $this->vatRate->id,
        'type' => 'Remplacement pneumatiques',
        'cost_ht' => 300.00,
        'odometer_at_maintenance' => 12000.00,
        'performed_at' => now()->subMonth(),
    ]);

    $this->vehicle->update(['odometer' => 20000.00]);
    expect($this->alertService->needsMaintenance($this->vehicle, 10000.00))->toBeFalse();

    $this->vehicle->update(['odometer' => 23000.00]);
    expect($this->alertService->needsMaintenance($this->vehicle, 10000.00))->toBeTrue();
});

test('retourne contrats expirés', function () {
    VehicleContract::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'type' => 'insurance',
        'policy_number' => 'POL-PAST',
        'start_date' => now()->subYears(2),
        'end_date' => now()->subDays(10),
        'annual_cost_ht' => 800.00,
    ]);

    $expired = $this->alertService->getExpiredContracts();

    expect($expired)->toHaveCount(1);
});

test('retourne tous les alertes véhicule', function () {
    VehicleContract::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'type' => 'insurance',
        'policy_number' => 'POL-EXP',
        'start_date' => now()->subYear(),
        'end_date' => now()->addDays(15),
        'annual_cost_ht' => 800.00,
    ]);

    $this->vehicle->update(['odometer' => 25000.00]);

    $allAlerts = $this->alertService->getAllAlerts($this->vehicle);

    expect($allAlerts)->toBeArray();
});
