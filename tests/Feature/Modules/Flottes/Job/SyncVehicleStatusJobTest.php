<?php

namespace Tests\Feature\Modules\Flottes\Jobs;

use App\Enums\Flottes\AssignmentStatus;
use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Flottes\SyncVehicleStatusJob;
use App\Models\Flottes\Vehicle;
use App\Models\Flottes\VehicleAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

describe('SyncVehicleStatusJob', function () {
    it('syncs vehicle statuses based on active assignments', function () {
        $assignedVehicle = Vehicle::factory()->create(['status' => VehicleStatus::AVAILABLE, 'reference' => 'AA-111-AA']);
        VehicleAssignment::factory()->create([
            'vehicle_id' => $assignedVehicle->id,
            'status' => AssignmentStatus::ACTIVE,
        ]);
        $assignedVehicle->updateQuietly(['status' => VehicleStatus::AVAILABLE]);

        $availableVehicle = Vehicle::factory()->create(['status' => VehicleStatus::ASSIGNED, 'reference' => 'BB-222-BB']);
        // No active assignment

        $brokenVehicle = Vehicle::factory()->create(['status' => VehicleStatus::BROKEN, 'reference' => 'CC-333-CC']);
        // No active assignment, but should stay broken

        Log::shouldReceive('info')->andReturnNull();

        $job = new SyncVehicleStatusJob;
        $job->handle();

        expect($assignedVehicle->fresh()->status)->toBe(VehicleStatus::ASSIGNED)
            ->and($availableVehicle->fresh()->status)->toBe(VehicleStatus::AVAILABLE)
            ->and($brokenVehicle->fresh()->status)->toBe(VehicleStatus::BROKEN);
    });
});
