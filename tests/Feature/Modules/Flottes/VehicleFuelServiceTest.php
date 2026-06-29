<?php

use App\Enums\Flottes\VehicleStatus;
use App\Enums\RH\TimeEntryStatus;
use App\Models\Flottes\FuelTransaction;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Models\RH\TimeEntry;
use App\Models\User;
use App\Services\Flottes\VehicleFuelService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->fuelService = app(VehicleFuelService::class);
    Notification::fake();

    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-FUEL-TEST',
        'license_plate' => 'AA123BB',
        'brand' => 'Renault',
        'model' => 'Master',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 50000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 28000.00,
    ]);

    $this->employee = Employee::factory()->create(['first_name' => 'Marc', 'last_name' => 'Dutronc']);
});

test('calcule ratios consommation lors plein standard', function () {
    $analysis = $this->fuelService->logFuelConsumption($this->vehicle, 40.0, 70.00, 50500.00, now());

    expect($analysis)->toBeArray()
        ->and($analysis['distance_travelled'])->toEqual(500.00)
        ->and($analysis['average_consumption_100km'])->toEqual(8.00)
        ->and($this->vehicle->refresh()->odometer)->toEqual(50500.00);
});

test('gère distance nulle', function () {
    $analysis = $this->fuelService->logFuelConsumption($this->vehicle, 5.0, 9.00, 50000.00, now());

    expect($analysis['distance_travelled'])->toEqual(0.0)
        ->and($analysis['average_consumption_100km'])->toEqual(0.0);
});

test('refuse plein si odomètre inférieur', function () {
    expect(fn () => $this->fuelService->logFuelConsumption($this->vehicle, 30.0, 50.00, 49900.00, now()))
        ->toThrow(Exception::class);
});

test('plein semaine avec pointage RH approuvé sans suspicion', function () {
    $mercrediMidi = Carbon::parse('2026-05-20 12:00:00');

    VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => $mercrediMidi->copy()->startOfDay()->addHours(8),
        'ended_at' => $mercrediMidi->copy()->startOfDay()->addHours(17),
        'start_odometer' => 50000.00,
        'status' => 'active',
    ]);

    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'date' => $mercrediMidi->toDateString(),
        'hours' => 7.00,
        'status' => TimeEntryStatus::APPROVED,
        'type' => 'normal',
    ]);

    $transaction = $this->fuelService->processAndAuditFuelTransaction(
        $this->vehicle,
        40.0,
        75.00,
        50400.00,
        $mercrediMidi,
        'TOTAL GORGES'
    );

    expect($transaction)->toBeInstanceOf(FuelTransaction::class)
        ->and($transaction->is_suspicious)->toBeFalse()
        ->and($transaction->employee_id)->toBe($this->employee->id);

    Notification::assertNothingSent();
});

test('plein dimanche identifié comme suspect', function () {
    User::factory()->create();
    $dimancheMatin = Carbon::parse('2026-05-17 10:00:00');

    VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => $dimancheMatin->copy()->subDays(2),
        'start_odometer' => 50000.00,
        'status' => 'active',
    ]);

    $transaction = $this->fuelService->processAndAuditFuelTransaction(
        $this->vehicle,
        45.0,
        80.00,
        50350.00,
        $dimancheMatin,
        'SHELL NANTES'
    );

    expect($transaction->is_suspicious)->toBeTrue()
        ->and($transaction->suspicion_reason)->toContain('dimanche');
});

test('plein sans affectation signalé comme siphonnage', function () {
    User::factory()->create();
    $dateTime = Carbon::parse('2026-05-18 15:30:00');

    $transaction = $this->fuelService->processAndAuditFuelTransaction(
        $this->vehicle,
        60.0,
        110.00,
        50180.00,
        $dateTime,
        'AS24 MONTAIGU'
    );

    expect($transaction->is_suspicious)->toBeTrue()
        ->and($transaction->employee_id)->toBeNull()
        ->and($transaction->suspicion_reason)->toContain('sans affectation de conducteur');
});

test('calcule consommation moyenne', function () {
    // Utiliser processAndAuditFuelTransaction pour créer des transactions en base
    $this->fuelService->processAndAuditFuelTransaction($this->vehicle, 40, 70, 50500, now()->subDays(3), 'Station A');
    $this->fuelService->processAndAuditFuelTransaction($this->vehicle, 50, 85, 51000, now()->subDays(2), 'Station B');

    $avg = $this->fuelService->getAverageConsumption($this->vehicle, now()->startOfMonth(), now());

    expect($avg)->toBeGreaterThan(0);
});

test('détecte anomalie consommation et signale le siphonnage', function () {
    User::factory()->create(['is_admin' => true]);
    \App\Models\Core\Setting::setValue('fuel_anomaly_threshold', 20);

    // 1. Transaction 1: 50L pour 500km (10L/100km)
    $this->fuelService->processAndAuditFuelTransaction($this->vehicle, 50, 70, 50500, now()->subDays(10), 'Station A');
    
    // 2. Transaction 2: 50L pour 500km (10L/100km)
    $this->fuelService->processAndAuditFuelTransaction($this->vehicle, 50, 70, 51000, now()->subDays(5), 'Station B');

    // 3. Affectation valide pour aujourd'hui
    $aujourdhui = now();
    VehicleAssignment::create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => $aujourdhui->copy()->startOfDay(),
        'status' => 'active',
    ]);
    TimeEntry::create([
        'employee_id' => $this->employee->id,
        'date' => $aujourdhui->toDateString(),
        'hours' => 7.00,
        'status' => TimeEntryStatus::APPROVED,
        'type' => 'normal',
    ]);

    // 4. Transaction anormale : 80L pour 100km (80L/100km) -> écart bien supérieur à 20%
    $transaction = $this->fuelService->processAndAuditFuelTransaction(
        $this->vehicle, 
        80, 
        120, 
        51100, 
        $aujourdhui, 
        'Station C'
    );

    expect($transaction->is_suspicious)->toBeTrue()
        ->and($transaction->suspicion_reason)->toContain('Surconsommation');
});


