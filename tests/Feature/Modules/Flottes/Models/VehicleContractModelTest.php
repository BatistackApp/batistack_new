<?php

use App\Models\Flottes\VehicleContract;

test('scope active filtre contrats en cours', function () {
    VehicleContract::factory()->count(2)->create([
        'end_date' => now()->addMonths(6),
        'start_date' => now()->startOfYear(),
        'annual_cost_ht' => 150,
    ]);
    VehicleContract::factory()->count(1)->create([
        'end_date' => now()->subDays(10),
        'start_date' => now()->startOfYear(),
        'annual_cost_ht' => 150,
    ]);

    $active = VehicleContract::active()->get();

    expect($active)->toHaveCount(2);
});

test('scope expiring filtre contrats -30j', function () {
    VehicleContract::factory()->create([
        'end_date' => now()->addDays(15),
        'start_date' => now()->startOfYear(),
        'annual_cost_ht' => 150,
    ]);
    VehicleContract::factory()->create([
        'end_date' => now()->addDays(45),
        'start_date' => now()->startOfYear(),
        'annual_cost_ht' => 150,
    ]);

    $expiring = VehicleContract::expiringsSoon(30)->get();

    expect($expiring)->toHaveCount(1);
});

test('relation vehicle charge véhicule', function () {
    $vehicle = \App\Models\Flottes\Vehicle::factory()->create();
    $contract = VehicleContract::factory()->create(['vehicle_id' => $vehicle->id, 'annual_cost_ht' => 1250]);

    $contract->load('vehicle');

    expect($contract->vehicle->id)->toBe($vehicle->id);
});

test('méthode getDaysUntilExpiry retourne jours restants', function () {
    $contract = VehicleContract::factory()->create([
        'end_date' => now()->addDays(16),
        'annual_cost_ht' => 150,
    ]);

    $days = $contract->getDaysUntilExpiration();

    expect($days)->toBe(15);
});
