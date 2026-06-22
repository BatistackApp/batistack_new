<?php

namespace Tests\Feature\Modules\Flottes;

use App\Models\Core\Company;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use App\Models\RH\Employee;
use App\Services\Flottes\FleetCostService;
use App\Services\Flottes\FleetDocumentService;
use Illuminate\Support\Facades\Storage;
use Mockery;

beforeEach(function () {
    Company::factory()->create();
    Storage::fake('public');

    $this->costService = Mockery::mock(FleetCostService::class);
    $this->costService->shouldReceive('calculateTco')->byDefault()->andReturn(0.0);
    $this->service = new FleetDocumentService($this->costService);

    $this->vehicle = Vehicle::factory()->create();
    $this->employee = Employee::factory()->create();
});

test('génère fiche mise à disposition', function () {
    $assignment = VehicleAssignment::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => now(),
        'ended_at' => now()->addHour(),
    ]);

    $path = $this->service->generateAssignmentForm($assignment);

    expect($path)->toContain('mise_a_disposition_')
        ->and($path)->toContain((string) $assignment->vehicle->reference)
        ->and($path)->toEndWith('.pdf');
});

test('stocke fichier dans flotte/assignments', function () {
    $assignment = VehicleAssignment::factory()->create([
        'vehicle_id' => $this->vehicle->id,
        'employee_id' => $this->employee->id,
        'started_at' => now(),
        'ended_at' => now()->addHour(),
    ]);

    $path = $this->service->generateAssignmentForm($assignment);

    expect($path)->toContain('flotte/assignments');
});

test('génère fiche véhicule', function () {
    $this->costService->shouldReceive('calculateTco')->andReturn(30000.0);
    $this->costService->shouldReceive('getCompleteCostSummary');

    $path = $this->service->generateVehicleFiche($this->vehicle);

    expect($path)->toContain('fiche_vehicule_')
        ->and($path)->toContain($this->vehicle->reference)
        ->and($path)->toEndWith('.pdf');
});

test('stocke dans flotte/vehicles', function () {
    $this->costService->shouldReceive('calculateTco')->andReturn(30000.0);
    $this->costService->shouldReceive('getCompleteCostSummary');

    $path = $this->service->generateVehicleFiche($this->vehicle);

    expect($path)->toContain('flotte/vehicles');
});

test('génère rapport maintenance', function () {
    $path = $this->service->generateMaintenanceReport($this->vehicle, now()->startOfYear(), now()->endOfYear());

    expect($path)->not->toBeNull();
});

test('génère rapport consommation', function () {
    $path = $this->service->generateConsumptionReport($this->vehicle, now()->startOfYear(), now()->endOfYear());

    expect($path)->not->toBeNull();
});

test('génère rapport utilisation', function () {
    $path = $this->service->generateUsageReport($this->vehicle, now()->startOfYear(), now()->endOfYear());

    expect($path)->not->toBeNull();
});

test('génère rapport amendes', function () {
    $path = $this->service->generateFinesReport($this->vehicle, now()->startOfYear(), now()->endOfYear());

    expect($path)->not->toBeNull();
});
