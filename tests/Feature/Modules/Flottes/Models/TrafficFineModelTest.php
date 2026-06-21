<?php

use App\Enums\Flottes\FineStatus;
use App\Models\Flottes\TrafficFine;

test('scope pending filtre amendes en attente', function () {
    TrafficFine::factory()->count(2)->create(['status' => FineStatus::RECEIVED]);
    TrafficFine::factory()->count(1)->create(['status' => FineStatus::PAID]);

    $pending = TrafficFine::pending()->get();

    expect($pending)->toHaveCount(2);
});

test('scope unpaid filtre amendes impayées', function () {
    TrafficFine::factory()->count(2)->create(['status' => FineStatus::RECEIVED]);
    TrafficFine::factory()->count(1)->create(['status' => FineStatus::PAID]);

    $unpaid = TrafficFine::unpaid()->get();

    expect($unpaid)->toHaveCount(2);
});

test('relation vehicle charge véhicule', function () {
    $vehicle = \App\Models\Flottes\Vehicle::factory()->create();
    $fine = TrafficFine::factory()->create(['vehicle_id' => $vehicle->id]);

    $fine->load('vehicle');

    expect($fine->vehicle->id)->toBe($vehicle->id);
});

test('relation employee charge salarié', function () {
    $employee = \App\Models\RH\Employee::factory()->create();
    $fine = TrafficFine::factory()->create(['employee_id' => $employee->id, 'amount' => 12.00]);

    $fine->load('employee');

    expect($fine->employee->id)->toBe($employee->id);
});

test('méthode isOverdue retourne vrai si 45j passés', function () {
    $fine = TrafficFine::factory()->create([
        'infraction_at' => now()->subDays(60),
        'status' => FineStatus::RECEIVED,
    ]);

    expect($fine->isOverdue())->toBeTrue();
});

test('méthode getDaysOverdue calcule délai', function () {
    $fine = TrafficFine::factory()->create([
        'infraction_at' => now()->subDays(50),
        'status' => FineStatus::RECEIVED,
    ]);

    $days = $fine->getDaysOverdue();

    expect($days)->toBeGreaterThan(0);
});
