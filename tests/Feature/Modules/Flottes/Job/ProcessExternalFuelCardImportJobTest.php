<?php

namespace Tests\Feature\Modules\Flottes\Jobs;

use App\Jobs\Flottes\ProcessExternalFuelCardImportJob;
use App\Models\Flottes\Vehicle;
use App\Models\User;
use App\Notifications\Flottes\FuelAnomalyAlertNotification;
use App\Services\Flottes\VehicleFuelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery;

uses(RefreshDatabase::class);

describe('ProcessExternalFuelCardImportJob', function () {
    it('processes fuel transactions and detects anomalies', function () {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        // Format of license plate is cleaned
        $vehicleNormal = Vehicle::factory()->create(['license_plate' => 'AB123CD']);
        $vehicleAnomaly = Vehicle::factory()->create(['license_plate' => 'EF456GH']);

        $transactions = [
            [
                'license_plate' => 'AB-123-CD', // Will be normalized to AB123CD
                'liters' => 50,
                'cost_ht' => 75,
                'odometer' => 100000,
                'date' => now()->toDateTimeString(),
                'station_name' => 'Station Normal',
            ],
            [
                'license_plate' => 'EF 456 GH', // Will be normalized to EF456GH
                'liters' => 100,
                'cost_ht' => 200, // Very expensive
                'odometer' => 103000, // 3000km diff
                'date' => now()->toDateTimeString(),
            ],
            [
                'license_plate' => 'UNKNOWN', // Doesn't exist
                'liters' => 50,
                'cost_ht' => 75,
                'odometer' => 100000,
                'date' => now()->toDateTimeString(),
            ],
        ];

        $serviceMock = Mockery::mock(VehicleFuelService::class);

        $serviceMock->shouldReceive('processAndAuditFuelTransaction')->times(2);

        $serviceMock->shouldReceive('logFuelConsumption')
            ->with(Mockery::on(fn ($v) => $v->id === $vehicleNormal->id), 50.0, 75.0, 100000.0, Mockery::any())
            ->andReturn([
                'average_consumption_100km' => 6.5,
                'distance_travelled' => 500,
                'cost_per_km' => 0.15,
            ]);

        $serviceMock->shouldReceive('logFuelConsumption')
            ->with(Mockery::on(fn ($v) => $v->id === $vehicleAnomaly->id), 100.0, 200.0, 103000.0, Mockery::any())
            ->andReturn([
                'average_consumption_100km' => 20.0, // > 15
                'distance_travelled' => 3000, // > 2000
                'cost_per_km' => 0.6, // > 0.5
            ]);

        Log::shouldReceive('warning')
            ->with('Import carburant : Véhicule non trouvé UNKNOWN')
            ->once();

        Log::shouldReceive('warning')
            ->with("Prix carburant élevé : {$vehicleAnomaly->reference} @ 0.6€/km")
            ->once();

        Log::shouldReceive('info')
            ->with('Import carburant : 2 transactions traitées, 1 erreurs')
            ->once();

        $job = new ProcessExternalFuelCardImportJob($transactions);
        $job->handle($serviceMock);

        Notification::assertSentTo(
            [$admin],
            FuelAnomalyAlertNotification::class,
            function ($notification) use ($vehicleAnomaly) {
                return (fn () => $this->vehicle->id)->call($notification) === $vehicleAnomaly->id
                    && str_contains((fn () => $this->anomalyMessage)->call($notification), 'Consommation excessive');
            }
        );

        Notification::assertSentTo(
            [$admin],
            FuelAnomalyAlertNotification::class,
            function ($notification) use ($vehicleAnomaly) {
                return (fn () => $this->vehicle->id)->call($notification) === $vehicleAnomaly->id
                    && str_contains((fn () => $this->anomalyMessage)->call($notification), 'Écart odomètre suspect');
            }
        );
    });

    it('handles job failure', function () {
        Log::shouldReceive('error')
            ->with('Job import carburant échoué : Critical failure')
            ->once();

        $job = new ProcessExternalFuelCardImportJob([]);
        $job->failed(new \Exception('Critical failure'));
    });
});
