<?php

namespace Tests\Feature\Modules\Flottes;

use App\Models\Chantiers\Chantier;
use App\Models\Flottes\Vehicle;
use App\Services\Flottes\RoutingOptimizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RoutingOptimizationService();
});

it('uses simulated routing when no API key is provided', function () {
    Config::set('services.google_maps.key', '');

    $vehicles = Vehicle::factory()->count(2)->create();
    $chantiers = Chantier::factory()->count(2)->create();

    $result = $this->service->optimizeAssignments($vehicles, $chantiers);

    expect($result)->toHaveCount(2)
        ->and($result[0])->toHaveKeys(['vehicle_id', 'chantier_id', 'distance_km', 'is_simulated'])
        ->and($result[0]['is_simulated'])->toBeTrue();
});

it('uses google maps distance matrix when api key is provided', function () {
    Config::set('services.google_maps.key', 'fake-key');

    $vehicles = Vehicle::factory()->count(1)->create(['license_plate' => 'CAM-123']);
    $chantiers = Chantier::factory()->count(1)->create(['name' => 'Chantier B']);

    Http::fake([
        'maps.googleapis.com/*' => Http::response([
            'status' => 'OK',
            'rows' => [
                [
                    'elements' => [
                        [
                            'status' => 'OK',
                            'distance' => ['value' => 15000], // 15km
                            'duration' => ['value' => 1200],  // 20 mins
                        ]
                    ]
                ]
            ]
        ], 200)
    ]);

    // L'instanciation doit se faire après le config::set pour prendre en compte la clé
    $service = new RoutingOptimizationService();
    $result = $service->optimizeAssignments($vehicles, $chantiers);

    expect($result)->toHaveCount(1)
        ->and($result[0]['distance_km'])->toBe(15.0)
        ->and($result[0]['duration_mins'])->toBe(20.0)
        ->and(isset($result[0]['is_simulated']))->toBeFalse();
});
