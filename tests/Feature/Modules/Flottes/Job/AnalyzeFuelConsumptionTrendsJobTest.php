<?php

namespace Tests\Feature\Modules\Flottes\Jobs;

use App\Jobs\Flottes\AnalyzeFuelConsumptionTrendsJob;
use App\Models\Flottes\Vehicle;
use App\Services\Flottes\VehicleFuelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;

uses(RefreshDatabase::class);

describe('AnalyzeFuelConsumptionTrendsJob', function () {
    it('analyzes fuel consumption and caches statistics', function () {
        $vehicle = Vehicle::factory()->create(['reference' => 'AB-123-CD']);

        $stats = [
            'monthly_consumption' => 150,
            'three_months_consumption' => 100, // +50% increase (150 > 100 * 1.2)
            'suspicious_transactions_count' => 2,
        ];

        $serviceMock = Mockery::mock(VehicleFuelService::class);
        $serviceMock->shouldReceive('getConsumptionStatistics')
            ->once()
            ->with(Mockery::on(fn ($v) => $v->id === $vehicle->id))
            ->andReturn($stats);

        app()->instance(VehicleFuelService::class, $serviceMock);

        Log::shouldReceive('warning')
            ->with("Consommation suspecte : {$vehicle->reference} - 2 transactions")
            ->once();

        Log::shouldReceive('warning')
            ->with("Hausse consommation : {$vehicle->reference} +20% vs 3 mois")
            ->once();

        Log::shouldReceive('info')
            ->with('Analyse consommation complétée pour 1 véhicules')
            ->once();

        $job = new AnalyzeFuelConsumptionTrendsJob;
        $job->handle($serviceMock);

        expect(Cache::has("fuel_stats_{$vehicle->id}"))->toBeTrue()
            ->and(Cache::get("fuel_stats_{$vehicle->id}"))->toBe($stats);
    });

    it('handles exception during analysis gracefully', function () {
        $vehicle = Vehicle::factory()->create(['reference' => 'AB-123-CD']);

        $serviceMock = Mockery::mock(VehicleFuelService::class);
        $serviceMock->shouldReceive('getConsumptionStatistics')
            ->andThrow(new \Exception('Test Error'));

        app()->instance(VehicleFuelService::class, $serviceMock);

        Log::shouldReceive('error')
            ->with("Analyse consommation {$vehicle->reference} : Test Error")
            ->once();

        Log::shouldReceive('info')->once();

        $job = new AnalyzeFuelConsumptionTrendsJob;
        $job->handle($serviceMock);

        expect(Cache::has("fuel_stats_{$vehicle->id}"))->toBeFalse();
    });
});
