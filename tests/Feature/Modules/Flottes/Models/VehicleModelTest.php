<?php

use App\Enums\Flottes\VehicleStatus;
use App\Enums\Flottes\VehicleType;
use App\Models\Flottes\FleetExpense;
use App\Models\Flottes\TrafficFine;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleMaintenance;

test('scope byStatus filtre correctement', function () {
    Vehicle::factory()->count(3)->create(['status' => VehicleStatus::AVAILABLE]);
    Vehicle::factory()->count(2)->create(['status' => VehicleStatus::ASSIGNED]);

    $available = Vehicle::byStatus(VehicleStatus::AVAILABLE)->get();

    expect($available)->toHaveCount(3)
        ->and($available->every(fn ($v) => $v->status === VehicleStatus::AVAILABLE))->toBeTrue();
});

test('scope byType filtre par type véhicule', function () {
    Vehicle::factory()->count(2)->create(['type' => VehicleType::UTILITY]);
    Vehicle::factory()->count(1)->create(['type' => VehicleType::SPECIAL]);

    $utilities = Vehicle::byType(VehicleType::UTILITY)->get();

    expect($utilities)->toHaveCount(2);
});

test('relation assignments charge correctement', function () {
    $vehicle = Vehicle::factory()->create();
    VehicleAssignment::factory()->count(3)->create(['vehicle_id' => $vehicle->id]);

    $vehicle->load('assignments');

    expect($vehicle->assignments)->toHaveCount(3);
});

test('relation maintenances charge correctement', function () {
    $vehicle = Vehicle::factory()->create();
    VehicleMaintenance::factory()->count(2)->create(['vehicle_id' => $vehicle->id]);

    $vehicle->load('maintenances');

    expect($vehicle->maintenances)->toHaveCount(2);
});

test('relation fines charge correctement', function () {
    $vehicle = Vehicle::factory()->create();
    TrafficFine::factory()->count(2)->create(['vehicle_id' => $vehicle->id, 'amount' => 100]);

    $vehicle->load('fines');

    expect($vehicle->fines)->toHaveCount(2);
});

test('méthode getTotalExpenses agrège dépenses', function () {
    $vehicle = Vehicle::factory()->create();

    FleetExpense::factory()->count(2)->create([
        'vehicle_id' => $vehicle->id,
        'type' => 'peage',
        'amount_ht' => 20,
        'amount_ttc' => 20,
    ]);

    $total = $vehicle->getTotalExpenses();

    expect($total)->toEqual(40);
});

test('méthode getActiveAssignment retourne affectation actuelle', function () {
    $vehicle = Vehicle::factory()->create();
    $activeAssignment = VehicleAssignment::factory()->create([
        'vehicle_id' => $vehicle->id,
        'status' => 'active',
        'started_at' => now(),
    ]);

    $active = $vehicle->getActiveAssignment();

    expect($active->id)->toBe($activeAssignment->id);
});
