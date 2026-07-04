<?php

use App\Jobs\Tiers\SynchronizeSirenJob;
use App\Models\Tiers\ThirdParty;
use App\Services\Core\SirenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

it('returns early and logs warning if SIRET is missing', function () {
    $thirdParty = ThirdParty::factory()->create(['siret' => null]);
    $job = new SynchronizeSirenJob($thirdParty);
    
    $mockService = Mockery::mock(SirenService::class);
    // Should not be called
    $mockService->shouldNotReceive('getInformation');

    Log::shouldReceive('warning')->once()->withArgs(function ($message) use ($thirdParty) {
        return str_contains($message, "ThirdParty {$thirdParty->id} has no SIRET");
    });

    $job->handle($mockService);
});

it('updates third party and logs info if siren info is found', function () {
    $thirdParty = ThirdParty::factory()->create([
        'siret' => '12345678900014',
        'legal_name' => 'Old Name',
    ]);
    
    $job = new SynchronizeSirenJob($thirdParty);
    
    $mockService = Mockery::mock(SirenService::class);
    $mockService->shouldReceive('getInformation')
        ->once()
        ->with('12345678900014')
        ->andReturn([
            'uniteLegale' => [
                'denominationUniteLegale' => 'New Legal Name'
            ]
        ]);

    Log::shouldReceive('info')->once()->withArgs(function ($message) use ($thirdParty) {
        return str_contains($message, "ThirdParty {$thirdParty->id} (SIRET: {$thirdParty->siret}) synchronized successfully.");
    });

    $job->handle($mockService);

    $thirdParty->refresh();
    expect($thirdParty->legal_name)->toBe('New Legal Name')
        ->and($thirdParty->last_siren_sync_at)->not->toBeNull();
});

it('logs warning if no siren info found', function () {
    $thirdParty = ThirdParty::factory()->create([
        'siret' => '12345678900014',
    ]);
    
    $job = new SynchronizeSirenJob($thirdParty);
    
    $mockService = Mockery::mock(SirenService::class);
    $mockService->shouldReceive('getInformation')
        ->once()
        ->with('12345678900014')
        ->andReturn(null);

    Log::shouldReceive('warning')->once()->withArgs(function ($message) use ($thirdParty) {
        return str_contains($message, "No SIREN information found for ThirdParty {$thirdParty->id}");
    });

    $job->handle($mockService);
});

it('catches exception and logs error', function () {
    $thirdParty = ThirdParty::factory()->create([
        'siret' => '12345678900014',
    ]);
    
    $job = new SynchronizeSirenJob($thirdParty);
    
    $mockService = Mockery::mock(SirenService::class);
    $mockService->shouldReceive('getInformation')
        ->once()
        ->with('12345678900014')
        ->andThrow(new \Exception('API Error'));

    Log::shouldReceive('error')->once()->withArgs(function ($message) use ($thirdParty) {
        return str_contains($message, "Failed to synchronize ThirdParty {$thirdParty->id}");
    });

    $job->handle($mockService);
});
