<?php

use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;

test('scope suspicious filtre transactions suspectes', function () {
    FuelTransaction::factory()->count(2)->create(['is_suspicious' => true]);
    FuelTransaction::factory()->count(1)->create(['is_suspicious' => false]);

    $suspicious = FuelTransaction::suspicious()->get();

    expect($suspicious)->toHaveCount(2);
});

test('scope recent filtre derniers 30 jours', function () {
    FuelTransaction::factory()->create(['purchased_at' => now()->subDays(10)]);
    FuelTransaction::factory()->create(['purchased_at' => now()->subDays(60)]);

    $recent = FuelTransaction::recent(30)->get();

    expect($recent)->toHaveCount(1);
});

test('relation vehicle charge véhicule', function () {
    $vehicle = \App\Models\Flottes\Vehicle::factory()->create();
    $fuel = FuelTransaction::factory()->create(['vehicle_id' => $vehicle->id]);

    $fuel->load('vehicle');

    expect($fuel->vehicle->id)->toBe($vehicle->id);
});

test('méthode getConsumption100km calcule ratio', function () {
    $vehicle = Vehicle::factory()->create();

    FuelTransaction::factory()->create([
        'vehicle_id' => $vehicle->id,
        'odometer' => 10000,
    ]);

    $fuel = FuelTransaction::factory()->create([
        'vehicle_id' => $vehicle->id,
        'liters' => 40,
        'odometer' => 10500,
    ]);

    $consumption = $fuel->getConsumptionRate($vehicle);

    expect($consumption)->toEqual(8.0);
});
