<?php

use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Flottes\RecalculateVehicleTcoJob;
use App\Models\Core\VatRate;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleContract;
use App\Models\Flottes\VehicleMaintenance;
use App\Models\Tiers\ThirdParty;
use App\Services\Flottes\FleetCostService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Bus::fake();
    Log::spy();

    $this->vatRate = VatRate::create([
        'name' => 'TVA 20%',
        'rate' => 20.0000,
        'is_default' => true,
    ]);

    $this->supplier = ThirdParty::create([
        'name' => 'Garage TCO',
        'type' => 'supplier',
        'is_active' => true,
    ]);

    $this->vehicle = Vehicle::create([
        'reference' => 'VEH-TCO-01',
        'license_plate' => 'AA111BB',
        'brand' => 'Peugeot',
        'model' => 'Boxer',
        'type' => 'utility',
        'fuel_type' => 'Diesel',
        'odometer' => 10000.00,
        'status' => VehicleStatus::AVAILABLE,
        'purchase_price' => 30000.00,
    ]);
});

test('dispatches job when vehicle price changes', function () {
    $this->vehicle->update(['purchase_price' => 35000]);

    Bus::assertDispatched(RecalculateVehicleTcoJob::class);
});

test('calculates TCO with maintenance', function () {
    expect($this->vehicle->purchase_price)->toEqual(30000.00);
    VehicleMaintenance::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'vat_rate_id' => $this->vatRate->id,
        'type' => 'Révision',
        'cost_ht' => 500.00,
        'odometer_at_maintenance' => 10000.00,
        'performed_at' => now(),
    ]);

    (new RecalculateVehicleTcoJob($this->vehicle))->handle(app(FleetCostService::class));

    $this->vehicle->refresh();
    expect($this->vehicle->tco_cache)->toEqual(30500.00);
});

test('includes contracts in TCO calculation', function () {
    VehicleContract::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'type' => 'insurance',
        'policy_number' => 'POL-TCO-99',
        'start_date' => now(),
        'end_date' => now()->addYear(),
        'annual_cost_ht' => 1000.00,
    ]);

    (new RecalculateVehicleTcoJob($this->vehicle))->handle(app(FleetCostService::class));

    $this->vehicle->refresh();
    expect($this->vehicle->tco_cache)->toBeGreaterThan(30000);
});

test('caches TCO for 7 days', function () {
    dispatch(new RecalculateVehicleTcoJob($this->vehicle));

    $this->vehicle->refresh();
    expect($this->vehicle->tco_cache)->not->toBeNull();
});

test('handles vehicle with no costs', function () {
    (new RecalculateVehicleTcoJob($this->vehicle))->handle(app(FleetCostService::class));

    $this->vehicle->refresh();
    expect($this->vehicle->tco_cache)->toEqual(30000.00);
});

test('recalculates on multiple dispatches', function () {
    (new RecalculateVehicleTcoJob($this->vehicle))->handle(app(FleetCostService::class));
    $tco1 = $this->vehicle->refresh()->tco_cache;

    VehicleMaintenance::create([
        'vehicle_id' => $this->vehicle->id,
        'supplier_id' => $this->supplier->id,
        'vat_rate_id' => $this->vatRate->id,
        'type' => 'Révision',
        'cost_ht' => 500.00,
        'odometer_at_maintenance' => 10000.00,
        'performed_at' => now(),
    ]);

    (new RecalculateVehicleTcoJob($this->vehicle))->handle(app(FleetCostService::class));
    $tco2 = $this->vehicle->refresh()->tco_cache;

    expect($tco2)->toBeGreaterThan($tco1);
});
