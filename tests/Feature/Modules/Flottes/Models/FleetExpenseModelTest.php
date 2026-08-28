<?php

use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\Vehicle;

test('scope suspicious filtre dépenses suspectes', function () {
    FleetExpense::factory()->count(2)->create(['is_suspicious' => true]);
    FleetExpense::factory()->count(1)->create(['is_suspicious' => false]);

    $suspicious = FleetExpense::suspicious()->get();

    expect($suspicious)->toHaveCount(2);
});

test('relation vehicle charge véhicule', function () {
    $vehicle = Vehicle::factory()->create();
    $expense = FleetExpense::factory()->create(['vehicle_id' => $vehicle->id]);

    $expense->load('vehicle');

    expect($expense->vehicle->id)->toBe($vehicle->id);
});
