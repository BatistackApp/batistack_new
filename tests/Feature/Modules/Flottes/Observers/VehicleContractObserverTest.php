<?php

use App\Jobs\Flottes\RecalculateVehicleTcoJob;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use App\Models\Tiers\ThirdParty;

test('dispatchera Job TCO à la création', function () {
    Bus::fake();

    $vehicle = Vehicle::factory()->create();
    $supplier = ThirdParty::factory()->create();

    VehicleContract::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => $supplier->id,
        'type' => 'insurance',
        'policy_number' => 'POL-001',
        'start_date' => now(),
        'end_date' => now()->addYear(),
        'annual_cost_ht' => 800,
    ]);

    Bus::assertDispatched(RecalculateVehicleTcoJob::class);
});

test('refuse suppression contrat actif', function () {
    $contract = VehicleContract::factory()->create([
        'end_date' => now()->addMonths(6),
        'annual_cost_ht' => 1250,
    ]);

    expect(fn () => $contract->delete())->toThrow(Exception::class);
});

test('permet suppression contrat expiré', function () {
    $contract = VehicleContract::factory()->create([
        'end_date' => now()->subDays(1),
        'start_date' => now()->subMonths(2),
        'annual_cost_ht' => 1250,
    ]);

    expect($contract->delete())->toEqual(1);
});

test('refuse création contrat avec date fin avant début', function () {
    $vehicle = Vehicle::factory()->create();
    $supplier = ThirdParty::factory()->create();

    expect(fn () => VehicleContract::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => $supplier->id,
        'type' => 'leasing',
        'policy_number' => 'ERR-DATE',
        'start_date' => now()->addMonth(),
        'end_date' => now(),
        'annual_cost_ht' => 1000,
    ]))->toThrow(Exception::class);
});

test('refuse création contrat avec coût annuel négatif', function () {
    $vehicle = Vehicle::factory()->create();
    $supplier = ThirdParty::factory()->create();

    expect(fn () => VehicleContract::create([
        'vehicle_id' => $vehicle->id,
        'supplier_id' => $supplier->id,
        'type' => 'insurance',
        'policy_number' => 'ERR-COST',
        'start_date' => now(),
        'end_date' => now()->addYear(),
        'annual_cost_ht' => -500,
    ]))->toThrow(Exception::class);
});

test('refuse création contrat sans fournisseur', function () {
    $vehicle = Vehicle::factory()->create();

    expect(fn () => VehicleContract::create([
        'vehicle_id' => $vehicle->id,
        'type' => 'insurance',
        'policy_number' => 'ERR-SUPP',
        'start_date' => now(),
        'end_date' => now()->addYear(),
        'annual_cost_ht' => 500,
    ]))->toThrow(Exception::class);
});

test('dispatchera Job TCO lors de modification ou suppression', function () {
    Bus::fake();

    $contract = VehicleContract::factory()->create([
        'annual_cost_ht' => 500,
        'start_date' => now()->subYear(),
        'end_date' => now()->subDay(),
    ]);

    // Test update
    $contract->update(['annual_cost_ht' => 600]);
    Bus::assertDispatched(RecalculateVehicleTcoJob::class);

    // Test delete
    $contract->delete();
    Bus::assertDispatched(RecalculateVehicleTcoJob::class);
});
