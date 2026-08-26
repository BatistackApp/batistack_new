<?php

namespace Tests\Feature\Modules\Flottes\Jobs;

use App\Enums\Flottes\VehicleStatus;
use App\Jobs\Flottes\CheckVehicleMaintenanceMilestonesJob;
use App\Models\Flottes\Vehicle;
use App\Models\User;
use App\Notifications\Flottes\MilestoneMaintenanceNotification;
use App\Services\Flottes\VehicleAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;

uses(RefreshDatabase::class);

describe('CheckVehicleMaintenanceMilestonesJob', function () {
    it('notifies admins and logs when maintenance is due or imminent', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);

        $vehicleDue = Vehicle::factory()->create(['status' => VehicleStatus::AVAILABLE, 'reference' => 'AA-111-AA']);
        $vehicleImminent = Vehicle::factory()->create(['status' => VehicleStatus::AVAILABLE, 'reference' => 'BB-222-BB']);
        $vehicleFine = Vehicle::factory()->create(['status' => VehicleStatus::AVAILABLE, 'reference' => 'CC-333-CC']);

        $serviceMock = Mockery::mock(VehicleAlertService::class);

        $serviceMock->shouldReceive('needsMaintenance')
            ->with(Mockery::on(fn ($v) => $v->id === $vehicleDue->id), 20000.00)
            ->andReturn(true);

        $serviceMock->shouldReceive('needsMaintenance')
            ->with(Mockery::on(fn ($v) => $v->id !== $vehicleDue->id), 20000.00)
            ->andReturn(false);

        $serviceMock->shouldReceive('getKilometersUntilMaintenance')
            ->with(Mockery::on(fn ($v) => $v->id === $vehicleDue->id), 20000.00)
            ->andReturn(-500); // Exceeded by 500

        $serviceMock->shouldReceive('getKilometersUntilMaintenance')
            ->with(Mockery::on(fn ($v) => $v->id === $vehicleImminent->id), 20000.00)
            ->andReturn(1500); // Within 2000km threshold

        $serviceMock->shouldReceive('getKilometersUntilMaintenance')
            ->with(Mockery::on(fn ($v) => $v->id === $vehicleFine->id), 20000.00)
            ->andReturn(10000); // Safe distance

        Log::shouldReceive('info')
            ->with("Maintenance due : {$vehicleDue->reference} - -500 km avant révision")
            ->once();

        Log::shouldReceive('warning')
            ->with("Maintenance imminente : {$vehicleImminent->reference} - 1500 km")
            ->once();

        Log::shouldReceive('info')
            ->with('Scan kilométrique : 1 véhicule(s) nécessitent révision')
            ->once();

        $job = new CheckVehicleMaintenanceMilestonesJob;
        $job->handle($serviceMock);

        Notification::assertSentTo(
            [$admin],
            MilestoneMaintenanceNotification::class,
            function ($notification) use ($vehicleDue) {
                return (fn () => $this->vehicle->id)->call($notification) === $vehicleDue->id;
            }
        );

        Notification::assertNotSentTo([$user], MilestoneMaintenanceNotification::class);
    });
});
