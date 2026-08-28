<?php

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Flottes\RecalculateVehicleTcoJob;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\RH\Employee;
use App\Models\Tiers\ThirdParty;
use App\Notifications\Flottes\MaintenanceScheduledNotification;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::spy();
});

test('marque véhicule MAINTENANCE pour types graves', function () {
    $vehicle = Vehicle::factory()->create();

    VehicleMaintenance::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => ThirdParty::factory()->create()->id,
        'vat_rate_id' => VatRate::factory()->create()->id,
        'type' => 'panne',
        'cost_ht' => 500,
        'performed_at' => now(),
    ]);

    expect($vehicle->refresh()->status)->toBe(VehicleStatus::MAINTENANCE);
});

test('met à jour odomètre si supérieur', function () {
    $vehicle = Vehicle::factory()->create(['odometer' => 10000]);

    VehicleMaintenance::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => ThirdParty::factory()->create()->id,
        'vat_rate_id' => VatRate::factory()->create()->id,
        'type' => 'révision',
        'cost_ht' => 200,
        'odometer_at_maintenance' => 10500,
        'performed_at' => now(),
    ]);

    expect($vehicle->refresh()->odometer)->toEqual(10500);
});

test('dispatchera Job TCO', function () {
    Bus::fake();

    $vehicle = Vehicle::factory()->create();

    VehicleMaintenance::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => ThirdParty::factory()->create()->id,
        'vat_rate_id' => VatRate::factory()->create()->id,
        'type' => 'révision',
        'cost_ht' => 150,
        'performed_at' => now(),
    ]);

    Bus::assertDispatched(RecalculateVehicleTcoJob::class);
});

test('refuse suppression maintenance récente', function () {
    $maintenance = VehicleMaintenance::factory()->create([
        'performed_at' => now()->subDays(5),
        'cost_ht' => 150,
    ]);

    expect(fn () => $maintenance->delete())->toThrow(Exception::class);
});

test('refuse création maintenance avec coût nul ou négatif', function () {
    $vehicle = Vehicle::factory()->create();

    expect(fn () => VehicleMaintenance::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => ThirdParty::factory()->create()->id,
        'vat_rate_id' => VatRate::factory()->create()->id,
        'type' => 'Réparation',
        'cost_ht' => 0,
        'performed_at' => now(),
    ]))->toThrow(Exception::class);
});

test('refuse création maintenance avec date future', function () {
    $vehicle = Vehicle::factory()->create();

    expect(fn () => VehicleMaintenance::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => ThirdParty::factory()->create()->id,
        'vat_rate_id' => VatRate::factory()->create()->id,
        'type' => 'Révision',
        'cost_ht' => 100,
        'performed_at' => now()->addDay(),
    ]))->toThrow(Exception::class);
});

test('notifie le conducteur actif de la maintenance', function () {
    Notification::fake();
    $vehicle = Vehicle::factory()->create();
    $employee = Employee::factory()->create();

    VehicleAssignment::create([
        'vehicle_id' => $vehicle->id,
        'employee_id' => $employee->id,
        'started_at' => now()->subHour(),
        'status' => AssignmentStatus::ACTIVE,
    ]);

    VehicleMaintenance::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => ThirdParty::factory()->create()->id,
        'vat_rate_id' => VatRate::factory()->create()->id,
        'type' => 'Révision',
        'cost_ht' => 100,
        'performed_at' => now(),
    ]);

    Notification::assertSentTo($employee, MaintenanceScheduledNotification::class);
});
