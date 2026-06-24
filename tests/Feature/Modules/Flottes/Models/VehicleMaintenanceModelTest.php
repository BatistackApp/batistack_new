<?php

use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\Tiers\ThirdParty;

test('scope critical filtre maintenances graves', function () {
    VehicleMaintenance::factory()->count(2)->create(['type' => 'panne', 'cost_ht' => 125.00]);
    VehicleMaintenance::factory()->count(1)->create(['type' => 'vidange', 'cost_ht' => 125.00]);

    $critical = VehicleMaintenance::critical()->get();

    expect($critical)->toHaveCount(2);
});

test('scope recent filtre 30 derniers jours', function () {
    VehicleMaintenance::factory()->create(['performed_at' => now()->subDays(10), 'cost_ht' => 125.00]);
    VehicleMaintenance::factory()->create(['performed_at' => now()->subDays(60), 'cost_ht' => 125.00]);

    $recent = VehicleMaintenance::recent(30)->get();

    expect($recent)->toHaveCount(1);
});

test('relation vehicle charge véhicule', function () {
    $vehicle = Vehicle::factory()->create();
    $maintenance = VehicleMaintenance::factory()->create(['vehicle_id' => $vehicle->id, 'cost_ht' => 125.00]);

    $maintenance->load('vehicle');

    expect($maintenance->vehicle->id)->toBe($vehicle->id);
});

test('relation supplier charge fournisseur', function () {
    $supplier = ThirdParty::factory()->create();
    $maintenance = VehicleMaintenance::factory()->create(['supplier_id' => $supplier->id, 'cost_ht' => 125.00]);

    $maintenance->load('supplier');

    expect($maintenance->supplier->id)->toBe($supplier->id);
});

test('méthode getCostTtc calcule montant TTC', function () {
    $vatRate = VatRate::factory()->create(['rate' => 20]);
    $maintenance = VehicleMaintenance::factory()->create([
        'cost_ht' => 100,
        'vat_rate_id' => $vatRate->id,
    ]);

    $ttc = $maintenance->getCostTtc();

    expect($ttc)->toEqual(120);
});
