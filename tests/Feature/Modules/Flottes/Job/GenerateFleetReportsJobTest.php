<?php

namespace Tests\Feature\Modules\Flottes\Jobs;

use App\Jobs\Flottes\GenerateFleetReportsJob;
use App\Models\Flottes\Vehicle;
use App\Services\Flottes\FleetDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;

uses(RefreshDatabase::class);

describe('GenerateFleetReportsJob', function () {
    it('generates and stores fleet reports', function () {
        $disk = config('filesystems.default');
        Storage::fake($disk);
        $vehicle = Vehicle::factory()->create(['reference' => 'TEST-VEHICLE']);

        $serviceMock = Mockery::mock(FleetDocumentService::class);
        $serviceMock->shouldReceive('generateVehicleFiche')
            ->once()
            ->with(Mockery::on(fn($v) => $v->id === $vehicle->id))
            ->andReturn('fiche data');

        $serviceMock->shouldReceive('generateMaintenanceReport')
            ->once()
            ->andReturn(['maintenance' => 'data']);

        $serviceMock->shouldReceive('generateConsumptionReport')
            ->once()
            ->andReturn(['consumption' => 'data']);

        $serviceMock->shouldReceive('generateUsageReport')
            ->once()
            ->andReturn(['usage' => 'data']);

        Log::shouldReceive('info')
            ->with("Rapports générés pour {$vehicle->reference}")
            ->once();

        Log::shouldReceive('info')
            ->with('Génération rapports flotte complétée')
            ->once();

        $job = new GenerateFleetReportsJob();
        $job->handle($serviceMock);

        $path = "fleet_reports/{$vehicle->reference}/" . now()->format('Y-m-d');
        
        $files = Storage::disk($disk)->files($path);
        
        expect($files)->toHaveCount(4);
    });

    it('handles exception during report generation', function () {
        $vehicle = Vehicle::factory()->create(['reference' => 'TEST-VEHICLE']);

        $serviceMock = Mockery::mock(FleetDocumentService::class);
        $serviceMock->shouldReceive('generateVehicleFiche')
            ->andThrow(new \Exception('Generation Error'));

        Log::shouldReceive('error')
            ->with("Erreur génération rapports {$vehicle->reference} : Generation Error")
            ->once();

        Log::shouldReceive('info')->once();

        $job = new GenerateFleetReportsJob();
        $job->handle($serviceMock);
    });
});
