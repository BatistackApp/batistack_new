<?php

use App\Enums\Flottes\FineStatus;
use App\Models\Flottes\TrafficFine;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Notifications\Flottes\TrafficFineReceivedNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->vehicle = Vehicle::factory()->create();
    $this->employee = Employee::factory()->create();
});

test('résout automatiquement conducteur', function () {
    VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => now()->subHour(),
        'ended_at' => now()->addHour(),
        'status' => 'active',
    ]);

    $fine = TrafficFine::create([
        'vehicle_id' => $this->vehicle->id,
        'reference' => 'PV-001',
        'infraction_at' => now(),
        'amount' => 50,
    ]);

    expect($fine->refresh()->employee_id)->toBe($this->employee->id);
});

test('notifie conducteur amende créée', function () {
    VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => now()->subHour(),
        'ended_at' => now()->addHour(),
        'status' => 'active',
    ]);

    TrafficFine::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'reference' => 'PV-001',
        'infraction_at' => now(),
        'amount' => 50,
    ]);

    Notification::assertSentTo($this->employee, TrafficFineReceivedNotification::class);
});

test('refuse suppression amende transmise', function () {
    $fine = TrafficFine::factory()->create([
        'status' => FineStatus::TRANSMITTED,
    ]);

    expect(fn () => $fine->delete())->toThrow(Exception::class);
});

test('refuse création amende avec montant nul ou négatif', function () {
    expect(fn () => TrafficFine::create([
        'vehicle_id' => $this->vehicle->id,
        'reference' => 'PV-ERR',
        'infraction_at' => now(),
        'amount' => 0,
    ]))->toThrow(Exception::class);
});

test('refuse création amende avec date future', function () {
    expect(fn () => TrafficFine::create([
        'vehicle_id' => $this->vehicle->id,
        'reference' => 'PV-FUTURE',
        'infraction_at' => now()->addDay(),
        'amount' => 50,
    ]))->toThrow(Exception::class);
});

test('refuse suppression amende payée', function () {
    $fine = TrafficFine::factory()->create([
        'status' => FineStatus::PAID,
        'amount' => 50,
    ]);

    expect(fn () => $fine->delete())->toThrow(Exception::class);
});
