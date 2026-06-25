<?php

use App\Enums\Flottes\FineStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Models\Flottes\TrafficFine;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Services\Flottes\TrafficFineService;
use Carbon\Carbon;

beforeEach(function () {
    $this->fineService = app(TrafficFineService::class);

    $this->employee = Employee::factory()->create([
        'first_name' => 'Adèle',
        'last_name' => 'Exarchopoulos',
    ]);

    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-FINE-01',
        'license_plate' => 'FF999GG',
        'brand' => 'Citroën',
        'model' => 'Jumper',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 8000.00,
        'status' => VehicleStatus::AVAILABLE,
    ]);
});

test('associe amende au conducteur d\'affectation clôturée', function () {
    $infractionTime = Carbon::parse('2026-05-15 10:15:00');

    VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => Carbon::parse('2026-05-15 08:00:00'),
        'ended_at' => Carbon::parse('2026-05-15 12:00:00'),
        'start_odometer' => 7800.00,
        'end_odometer' => 7900.00,
        'status' => 'completed',
    ]);

    $fine = $this->fineService->registerFine(
        $this->vehicle,
        'PV-EXCES-9988',
        $infractionTime,
        45.00,
        1
    );

    expect($fine->refresh()->employee_id)->toBe($this->employee->id)
        ->and($fine->status)->toBe(FineStatus::RECEIVED)
        ->and($fine->amount)->toEqual(45.00)
        ->and($fine->points_deducted)->toBe(1);
});

test('associe amende au conducteur affectation ouverte', function () {
    $infractionTime = Carbon::parse('2026-05-17 14:30:00');

    VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => Carbon::parse('2026-05-17 07:30:00'),
        'ended_at' => null,
        'start_odometer' => 7900.00,
        'status' => 'active',
    ]);

    $fine = $this->fineService->registerFine(
        $this->vehicle,
        'PV-RED-LIGHT',
        $infractionTime,
        135.00,
        4
    );

    expect($fine->refresh()->employee_id)->toBe($this->employee->id);
});

test('enregistre amende sans conducteur sans affectation', function () {
    $infractionTime = Carbon::parse('2026-05-17 23:30:00');

    VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => Carbon::parse('2026-05-17 08:00:00'),
        'ended_at' => Carbon::parse('2026-05-17 18:00:00'),
        'start_odometer' => 7900.00,
        'end_odometer' => 7980.00,
        'status' => 'completed',
    ]);

    $fine = $this->fineService->registerFine(
        $this->vehicle,
        'PV-NIGHT-0011',
        $infractionTime,
        135.00,
        0
    );

    expect($fine->refresh()->employee_id)->toBeNull();
});

test('marque amende payée', function () {
    $fine = TrafficFine::factory()->create([
        'status' => FineStatus::RECEIVED,
    ]);

    $this->fineService->markAsPaid($fine);

    expect($fine->refresh()->status)->toBe(FineStatus::PAID);
});

test('marque amende contestée', function () {
    $fine = TrafficFine::factory()->create([
        'status' => FineStatus::RECEIVED,
    ]);

    $this->fineService->markAsDisputed($fine);

    expect($fine->refresh()->status)->toBe(FineStatus::DISPUTED);
});

test('retourne amendes en attente', function () {
    TrafficFine::factory()->count(2)->create(['status' => FineStatus::RECEIVED, 'vehicle_id' => $this->vehicle->id]);
    TrafficFine::factory()->count(1)->create(['status' => FineStatus::PAID, 'vehicle_id' => $this->vehicle->id]);

    $pending = $this->fineService->getPendingFines($this->vehicle);

    expect($pending)->toHaveCount(2);
});

test('calcule total amendes en attente', function () {
    TrafficFine::factory()->create(['status' => FineStatus::RECEIVED, 'amount' => 100, 'vehicle_id' => $this->vehicle->id]);
    TrafficFine::factory()->create(['status' => FineStatus::RECEIVED, 'amount' => 150, 'vehicle_id' => $this->vehicle->id]);

    $total = $this->fineService->getPendingFinesTotal($this->vehicle);

    expect($total)->toEqual(250);
});

test('détecte conducteur récidiviste', function () {
    TrafficFine::factory()->count(4)->create([
        'employee_id' => $this->employee->id,
        'status' => FineStatus::PAID,
        'vehicle_id' => $this->vehicle->id,
        'amount' => 34,
    ]);

    $isRecidivist = $this->fineService->isRecidivistDriver($this->vehicle);

    expect($isRecidivist)->toBeTrue();
});

test('calcule le total des points déduits sur une période', function () {
    TrafficFine::factory()->create(['vehicle_id' => $this->vehicle->id, 'points_deducted' => 2, 'infraction_at' => now()->subDays(2)]);
    TrafficFine::factory()->create(['vehicle_id' => $this->vehicle->id, 'points_deducted' => 3, 'infraction_at' => now()->subDays(5)]);

    $points = $this->fineService->getTotalPointsDeducted($this->vehicle, now()->subWeek(), now());

    expect($points)->toBe(5);
});

test('obtient les statistiques des amendes', function () {
    TrafficFine::factory()->create(['vehicle_id' => $this->vehicle->id, 'amount' => 100, 'status' => FineStatus::PAID, 'points_deducted' => 1]);
    TrafficFine::factory()->create(['vehicle_id' => $this->vehicle->id, 'amount' => 200, 'status' => FineStatus::RECEIVED, 'points_deducted' => 2]);

    $stats = $this->fineService->getFineStatistics($this->vehicle);

    expect($stats)->toBeArray()
        ->and($stats['total_fines'])->toBe(2)
        ->and($stats['pending_fines'])->toBe(1)
        ->and($stats['pending_amount'])->toBe(200.0)
        ->and($stats['paid_fines'])->toBe(1)
        ->and($stats['paid_amount'])->toBe(100.0)
        ->and($stats['total_points_deducted'])->toBe(3)
        ->and($stats['average_fine_amount'])->toBe(150.0);
});
