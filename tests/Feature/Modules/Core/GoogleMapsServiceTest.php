<?php

use App\Services\Core\GoogleMapsService;
use App\Services\Core\SettingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->settingService = Mockery::mock(SettingService::class);
});

describe('GoogleMapsService', function () {
    test('geocodeAddress returns null if no API key', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn(null);
        $service = new GoogleMapsService($this->settingService);
        
        expect($service->geocodeAddress('Paris'))->toBeNull();
    });

    test('geocodeAddress returns formatted location on success', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn('dummy-key');
        $service = new GoogleMapsService($this->settingService);
        
        Http::fake([
            'maps.googleapis.com/maps/api/geocode/json*' => Http::response([
                'results' => [
                    [
                        'geometry' => ['location' => ['lat' => 48.8566, 'lng' => 2.3522]],
                        'formatted_address' => 'Paris, France'
                    ]
                ]
            ], 200)
        ]);

        $result = $service->geocodeAddress('Paris');
        
        expect($result)->toBeArray()
            ->and($result['lat'])->toBe(48.8566)
            ->and($result['lng'])->toBe(2.3522)
            ->and($result['formatted_address'])->toBe('Paris, France');
    });

    test('geocodeAddress returns null on failure', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn('dummy-key');
        $service = new GoogleMapsService($this->settingService);
        
        Http::fake([
            'maps.googleapis.com/maps/api/geocode/json*' => Http::response([], 404)
        ]);

        expect($service->geocodeAddress('Unknown'))->toBeNull();
    });

    test('getDistanceMatrix returns null if no API key', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn(null);
        $service = new GoogleMapsService($this->settingService);
        
        expect($service->getDistanceMatrix('Paris', 'Lyon'))->toBeNull();
    });

    test('getDistanceMatrix returns formatted data on success', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn('dummy-key');
        $service = new GoogleMapsService($this->settingService);
        
        Http::fake([
            'maps.googleapis.com/maps/api/distancematrix/json*' => Http::response([
                'rows' => [
                    [
                        'elements' => [
                            [
                                'status' => 'OK',
                                'distance' => ['text' => '400 km', 'value' => 400000],
                                'duration' => ['text' => '4 hours', 'value' => 14400]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $service->getDistanceMatrix('Paris', 'Lyon');
        
        expect($result)->toBeArray()
            ->and($result['distance_text'])->toBe('400 km')
            ->and($result['distance_value'])->toBe(400000)
            ->and($result['duration_text'])->toBe('4 hours')
            ->and($result['duration_value'])->toBe(14400);
    });

    test('getDistanceMatrix returns null on failure', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn('dummy-key');
        $service = new GoogleMapsService($this->settingService);
        
        Http::fake([
            'maps.googleapis.com/maps/api/distancematrix/json*' => Http::response([], 500)
        ]);

        expect($service->getDistanceMatrix('Paris', 'Lyon'))->toBeNull();
    });

    test('optimizeRoute returns null if no API key or empty waypoints', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn(null);
        $service = new GoogleMapsService($this->settingService);
        
        Log::shouldReceive('error')->once(); // It logs an error when missing key/waypoints
        expect($service->optimizeRoute('Paris', 'Lyon', ['Marseille']))->toBeNull();

        $this->settingService = Mockery::mock(SettingService::class);
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn('dummy-key');
        $service = new GoogleMapsService($this->settingService);
        
        Log::shouldReceive('error')->once(); // It logs an error when empty waypoints
        expect($service->optimizeRoute('Paris', 'Lyon', []))->toBeNull();
    });

    test('optimizeRoute formats legs and waypoint order properly', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn('dummy-key');
        $service = new GoogleMapsService($this->settingService);
        
        Http::fake([
            'routes.googleapis.com/*' => Http::response([
                'routes' => [
                    [
                        'optimizedIntermediateWaypointIndex' => [1, 0],
                        'legs' => [
                            ['duration' => '3600s', 'distanceMeters' => 100000],
                            ['duration' => '7200s', 'distanceMeters' => 200000]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $result = $service->optimizeRoute('Paris', 'Lyon', ['48.8,2.3', 'Marseille']);
        
        expect($result)->toBeArray()
            ->and($result['waypoint_order'])->toBe([1, 0])
            ->and($result['legs'])->toHaveCount(2)
            ->and($result['legs'][0]['duration']['value'])->toBe(3600)
            ->and($result['legs'][0]['distance']['value'])->toBe(100000);
    });

    test('optimizeRoute returns null on API error', function () {
        $this->settingService->shouldReceive('get')->with('google_maps_key')->andReturn('dummy-key');
        $service = new GoogleMapsService($this->settingService);
        
        Http::fake([
            'routes.googleapis.com/*' => Http::response([], 500)
        ]);

        Log::shouldReceive('error')->once();
        expect($service->optimizeRoute('Paris', 'Lyon', ['Marseille']))->toBeNull();
    });
});
