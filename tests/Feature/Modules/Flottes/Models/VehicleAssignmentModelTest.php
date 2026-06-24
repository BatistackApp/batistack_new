<?php

use App\Enums\Flottes\AssignmentStatus;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;

test('scope active filtre affectations en cours', function () {
    VehicleAssignment::factory()->count(2)->create(['status' => AssignmentStatus::ACTIVE]);
    VehicleAssignment::factory()->count(1)->create(['status' => AssignmentStatus::COMPLETED]);

    $active = VehicleAssignment::active()->get();

    expect($active)->toHaveCount(2);
});

test('scope completed filtre affectations terminées', function () {
    VehicleAssignment::factory()->count(1)->create(['status' => AssignmentStatus::ACTIVE]);
    VehicleAssignment::factory()->count(2)->create(['status' => AssignmentStatus::COMPLETED]);

    $completed = VehicleAssignment::completed()->get();

    expect($completed)->toHaveCount(2);
});

test('relation vehicle charge véhicule', function () {
    $vehicle = Vehicle::factory()->create();
    $assignment = VehicleAssignment::factory()->create(['vehicle_id' => $vehicle->id]);

    $assignment->load('vehicle');

    expect($assignment->vehicle->id)->toBe($vehicle->id);
});

test('relation employee charge salarié', function () {
    $employee = Employee::factory()->create();
    $assignment = VehicleAssignment::factory()->create(['employee_id' => $employee->id]);

    $assignment->load('employee');

    expect($assignment->employee->id)->toBe($employee->id);
});

test('méthode getDurationInHours calcule durée', function () {
    $startedAt = now()->subHours(3);
    $assignment = VehicleAssignment::factory()->create([
        'started_at' => $startedAt,
        'ended_at' => $startedAt->copy()->addHours(3), // On utilise une base fixe
    ]);

    $duration = $assignment->getDurationInHours();

    expect($duration)->toEqual(3.0);
});

test('méthode getKilometersTravel calcule distance', function () {
    $assignment = VehicleAssignment::factory()->create([
        'start_odometer' => 10000,
        'end_odometer' => 10150,
    ]);

    $km = $assignment->getDistance();

    expect($km)->toEqual(150);
});

test('méthode isOverdue retourne vrai si > 2h après ended_at', function () {
    $assignment = VehicleAssignment::factory()->create([
        'started_at' => now()->subHours(6),
        'ended_at' => now()->subHours(3),
        'status' => AssignmentStatus::ACTIVE,
    ]);

    expect($assignment->isOverdue())->toBeTrue();
});
