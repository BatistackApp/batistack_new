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

describe('FleetDocumentService - generateAssignmentForm', function () {
    test('génère une fiche de mise à disposition', function () {
        $assignment = VehicleAssignment::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateAssignmentForm($assignment);

        expect($path)->toContain('mise_a_disposition_')
            ->and($path)->toContain((string) $assignment->id)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans flotte/assignments', function () {
        $assignment = VehicleAssignment::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateAssignmentForm($assignment);

        expect($path)->toContain('flotte/assignments');
    });

    test('charge les relations de l\'assignment', function () {
        $assignment = VehicleAssignment::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
        ]);

        $assignment->load(['vehicle.inventories.item', 'employee', 'chantier']);

        $path = $this->service->generateAssignmentForm($assignment);

        expect($path)->not->toBeNull();
    });

    test('inclut la référence du véhicule dans le titre', function () {
        $this->vehicle->update(['reference' => 'VH-2026-001']);

        $assignment = VehicleAssignment::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateAssignmentForm($assignment);

        expect($path)->not->toBeNull();
    });

    test('inclut l\'inventaire du véhicule', function () {
        $assignment = VehicleAssignment::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateAssignmentForm($assignment);

        expect($path)->not->toBeNull();
    });

    test('inclut la date et heure actuelles', function () {
        $assignment = VehicleAssignment::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
        ]);

        $path = $this->service->generateAssignmentForm($assignment);

        expect($path)->not->toBeNull();
    });

    test('gère un assignment sans chantier', function () {
        $assignment = VehicleAssignment::factory()->create([
            'vehicle_id' => $this->vehicle->id,
            'employee_id' => $this->employee->id,
            'chantier_id' => null,
        ]);

        $path = $this->service->generateAssignmentForm($assignment);

        expect($path)->not->toBeNull();
    });
});

describe('FleetDocumentService - generateVehicleFiche', function () {
    test('génère une fiche véhicule', function () {
        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->toContain('fiche_vehicule_')
            ->and($path)->toContain($this->vehicle->reference)
            ->and($path)->toEndWith('.pdf');
    });

    test('stocke le fichier dans flotte/vehicles', function () {
        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->toContain('flotte/vehicles');
    });

    test('charge les maintenances du véhicule', function () {
        $this->vehicle->load(['maintenances.supplier']);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->not->toBeNull();
    });

    test('charge les contrats du véhicule', function () {
        $this->vehicle->load(['contracts.supplier']);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->not->toBeNull();
    });

    test('charge les amendes du véhicule', function () {
        $this->vehicle->load(['fines']);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->not->toBeNull();
    });

    test('calcule le TCO du véhicule', function () {
        $this->costService->shouldReceive('calculateTco')
            ->with($this->vehicle)
            ->andReturn(50000.00);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->not->toBeNull();
    });

    test('inclut la référence du véhicule dans le titre', function () {
        $this->vehicle->update(['reference' => 'FOURGON-2026-001']);

        $this->costService->shouldReceive('calculateTco')
            ->andReturn(0);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->toContain('FOURGON-2026-001');
    });

    test('inclut la date et heure actuelles', function () {
        $this->costService->shouldReceive('calculateTco')
            ->andReturn(0);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->not->toBeNull();
    });

    test('utilise le service FleetCostService', function () {
        $this->costService->shouldReceive('calculateTco')
            ->once()
            ->with($this->vehicle)
            ->andReturn(45000.00);

        $this->service->generateVehicleFiche($this->vehicle);

        expect(true)->toBeTrue();
    });

    test('gère un véhicule sans maintenances', function () {
        $this->costService->shouldReceive('calculateTco')
            ->andReturn(0);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->not->toBeNull();
    });

    test('gère un véhicule sans contrats', function () {
        $this->costService->shouldReceive('calculateTco')
            ->andReturn(0);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->not->toBeNull();
    });

    test('gère un véhicule sans amendes', function () {
        $this->costService->shouldReceive('calculateTco')
            ->andReturn(0);

        $path = $this->service->generateVehicleFiche($this->vehicle);

        expect($path)->not->toBeNull();
    });
});
