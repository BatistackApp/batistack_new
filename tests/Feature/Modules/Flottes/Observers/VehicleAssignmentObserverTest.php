<?php

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Chantiers\RecalculateChantierProgressJob;
use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Notifications\Flottes\VehicleAssignmentStartingNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Log::spy();
    Notification::fake();
});

test('marque véhicule ASSIGNED à la création', function () {
    $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::AVAILABLE]);
    $employee = Employee::factory()->create();

    VehicleAssignment::create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'started_at' => now(),
        'status' => AssignmentStatus::ACTIVE,
    ]);

    expect($vehicle->refresh()->status)->toBe(VehicleStatus::ASSIGNED);
});

test('libère véhicule à la clôture', function () {
    $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::ASSIGNED]);
    $employee = Employee::factory()->create();

    $assignment = VehicleAssignment::create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'status' => AssignmentStatus::ACTIVE,
        'started_at' => now()->subHours(2),
        'end_odometer' => 1000,
        'start_odometer' => 900,
    ]);

    $assignment->update(['status' => AssignmentStatus::COMPLETED, 'ended_at' => now()]);

    expect($vehicle->refresh()->status)->toBe(VehicleStatus::AVAILABLE);
});

test('libère véhicule à l\'annulation', function () {
    $vehicle = Vehicle::factory()->create(['status' => VehicleStatus::ASSIGNED]);
    $employee = Employee::factory()->create();

    $assignment = VehicleAssignment::create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'status' => AssignmentStatus::ACTIVE,
        'started_at' => now(),
    ]);

    $assignment->update(['status' => AssignmentStatus::CANCELLED]);

    expect($vehicle->refresh()->status)->toBe(VehicleStatus::AVAILABLE);
});

test('refuse suppression affectation active', function () {
    $assignment = VehicleAssignment::factory()->create(['status' => AssignmentStatus::ACTIVE, 'started_at' => now(), 'ended_at' => now()->addDay()]);

    expect(fn () => $assignment->delete())->toThrow(Exception::class);
});

test('permet suppression affectation complétée', function () {
    $assignment = VehicleAssignment::factory()->create(['status' => AssignmentStatus::COMPLETED, 'started_at' => now(), 'ended_at' => now()->addDay()]);

    expect($assignment->delete())->toEqual(1);
});

test('enregistre création en log', function () {
    $vehicle = Vehicle::factory()->create();
    $employee = Employee::factory()->create();

    VehicleAssignment::create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'started_at' => now(),
        'status' => AssignmentStatus::ACTIVE,
    ]);

    Log::shouldHaveReceived('info');
});

test('dispatchera Job TCO si chantier présent', function () {
    Bus::fake();

    $vehicle = Vehicle::factory()->create();
    $employee = Employee::factory()->create();
    $chantier = Chantier::factory()->create();

    $assignment = VehicleAssignment::create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'chantier_id' => $chantier->id,
        'status' => AssignmentStatus::ACTIVE,
        'started_at' => now()->subHour(),
        'ended_at' => now()->addDay(),
        'end_odometer' => 1000,
        'start_odometer' => 900,
    ]);

    $assignment->update(['status' => AssignmentStatus::COMPLETED, 'ended_at' => now()]);
    Bus::assertDispatched(RecalculateChantierProgressJob::class);
});
