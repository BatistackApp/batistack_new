<?php

use App\Models\RH\TimeEntry;
use App\Models\RH\Employee;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Enums\RH\TimeEntryType;
use App\Services\RH\TimeEntryAnomalyDetectorService;
use Carbon\Carbon;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('detects anomaly when vehicle duration is too low', function () {
    $date = Carbon::today();
    $employee = Employee::factory()->create();
    
    // 10 hours of work
    TimeEntry::factory()->create([
        'employee_id' => $employee->id,
        'date' => $date,
        'type' => TimeEntryType::NORMAL,
        'is_workshop' => false,
        'hours' => 10,
    ]);

    // Only 2 hours of vehicle usage
    $vehicle = Vehicle::factory()->create();
    VehicleAssignment::factory()->create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'status' => \App\Enums\Flottes\AssignmentStatus::COMPLETED,
        'started_at' => $date->copy()->setHour(8)->setMinute(0),
        'ended_at' => $date->copy()->setHour(10)->setMinute(0),
    ]);

    $service = new TimeEntryAnomalyDetectorService();
    $count = $service->detectForDate($date, 1.0);

    expect($count)->toBe(1);

    $entry = TimeEntry::first();
    expect($entry->is_anomaly)->toBeTrue()
        ->and($entry->anomaly_reason)->toContain('largement supérieures');
});

it('does not detect anomaly if vehicle duration is close enough', function () {
    $date = Carbon::today();
    $employee = Employee::factory()->create();
    
    // 8 hours of work
    TimeEntry::factory()->create([
        'employee_id' => $employee->id,
        'date' => $date,
        'type' => TimeEntryType::NORMAL,
        'is_workshop' => false,
        'hours' => 8,
    ]);

    // 7.5 hours of vehicle usage (difference is 0.5 < 1)
    $vehicle = Vehicle::factory()->create();
    VehicleAssignment::factory()->create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'status' => \App\Enums\Flottes\AssignmentStatus::COMPLETED,
        'started_at' => $date->copy()->setHour(8)->setMinute(0),
        'ended_at' => $date->copy()->setHour(15)->setMinute(30),
    ]);

    $service = new TimeEntryAnomalyDetectorService();
    $count = $service->detectForDate($date, 1.0);

    expect($count)->toBe(0)
        ->and(TimeEntry::first()->is_anomaly)->toBeFalse();
});

it('ignores sedentary workshop time entries', function () {
    $date = Carbon::today();
    $employee = Employee::factory()->create();
    
    // 10 hours of work but ATELIER
    TimeEntry::factory()->create([
        'employee_id' => $employee->id,
        'date' => $date,
        'type' => TimeEntryType::NORMAL,
        'is_workshop' => true,
        'hours' => 10,
    ]);

    // NO vehicle assignment at all

    $service = new TimeEntryAnomalyDetectorService();
    $count = $service->detectForDate($date, 1.0);

    expect($count)->toBe(0)
        ->and(TimeEntry::first()->is_anomaly)->toBeFalse();
});
